<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use RuntimeException;
use Simtabi\Laranail\Package\Management\Contracts\ActivationStore;
use Simtabi\Laranail\Package\Management\Contracts\LoaderAdapter;
use Simtabi\Laranail\Package\Management\Contracts\PublishesAssets;
use Simtabi\Laranail\Package\Management\Contracts\RecordsInstall;
use Simtabi\Laranail\Package\Management\Contracts\RunsMigrations;
use Simtabi\Laranail\Package\Management\Contracts\SeedsSettings;
use Simtabi\Laranail\Package\Management\Events\ExtensionActivated;
use Simtabi\Laranail\Package\Management\Events\ExtensionDeactivated;
use Simtabi\Laranail\Package\Management\Events\ExtensionInstalled;
use Simtabi\Laranail\Package\Management\Events\ExtensionRemoved;
use Simtabi\Laranail\Package\Management\Events\ExtensionUpdated;
use Simtabi\Laranail\Package\Management\Support\DependencyResolver;

/**
 * Orchestrates the runtime loader: registers active modules/plugins in dependency
 * order and drives the activation lifecycle (dependency guards, per-extension hooks
 * and events). The public runtime API behind the `Extensions` facade + helpers.
 */
final readonly class ExtensionManager
{
    public function __construct(
        private ExtensionRepository $repository,
        private DependencyResolver $resolver,
        private LoaderAdapter $adapter,
        private ActivationStore $store,
        private Dispatcher $events,
        private Container $container,
    ) {}

    /**
     * Register every active, runtime-loaded extension (modules/plugins), dependency
     * order first. Packages are Composer-autoloaded already and are not registered here.
     */
    public function boot(): void
    {
        $runtime = array_values(array_filter(
            $this->repository->active(),
            static fn (Extension $e): bool => $e->isRuntimeLoaded(),
        ));

        foreach ($this->resolver->sort($runtime) as $extension) {
            $this->adapter->registerAutoload($extension);
            $this->adapter->registerProviders($extension);
        }
    }

    /** @return list<Extension> */
    public function all(): array
    {
        return $this->repository->all();
    }

    /** @return list<Extension> */
    public function active(): array
    {
        return $this->repository->active();
    }

    /** @return list<Extension> */
    public function modules(): array
    {
        return $this->repository->byRole('module');
    }

    /** @return list<Extension> */
    public function plugins(): array
    {
        return $this->repository->byRole('plugin');
    }

    public function find(string $id): ?Extension
    {
        return $this->repository->find($id);
    }

    public function enable(string $id): void
    {
        $extension = $this->requireExtension($id);

        foreach ($extension->require as $dependency) {
            if (! $this->store->isActive($dependency)) {
                throw new RuntimeException("Extension [{$id}] requires [{$dependency}], which is not active.");
            }
        }

        $this->store->activate($id);
        $this->repository->forget();

        if (($hook = $this->resolveHook($extension)) !== null && method_exists($hook, 'activated')) {
            $hook->activated($extension);
        }

        $this->events->dispatch(new ExtensionActivated($extension));
    }

    public function disable(string $id): void
    {
        $extension = $this->repository->find($id);

        foreach ($this->repository->active() as $active) {
            if (in_array($id, $active->require, true)) {
                throw new RuntimeException("Cannot disable [{$id}]: [{$active->id}] requires it.");
            }
        }

        $this->store->deactivate($id);
        $this->repository->forget();

        if ($extension instanceof Extension) {
            if (($hook = $this->resolveHook($extension)) !== null && method_exists($hook, 'deactivated')) {
                $hook->deactivated($extension);
            }

            $this->events->dispatch(new ExtensionDeactivated($extension));
        }
    }

    /**
     * Install: activate the extension (validating dependencies) and run its own
     * migrations, if the adapter supports it. Schema is applied after activation so
     * the extension is registered before its migrations reference its classes.
     */
    public function install(string $id): void
    {
        $extension = $this->requireExtension($id);

        $this->enable($id);

        if ($this->store instanceof RecordsInstall) {
            $this->store->recordInstall($id, $extension->version);
        }

        if ($this->store instanceof SeedsSettings && $extension->defaultSettings !== []) {
            $this->store->seedSettings($id, $extension->defaultSettings);
        }

        if ($this->adapter instanceof RunsMigrations) {
            $this->adapter->runMigrations($extension);
        }

        if ($this->adapter instanceof PublishesAssets) {
            $this->adapter->publishAssets($extension);
        }

        if (($hook = $this->resolveHook($extension)) !== null && method_exists($hook, 'installed')) {
            $hook->installed($extension);
        }

        $this->events->dispatch(new ExtensionInstalled($extension));
    }

    /** Update: run any pending migrations for an already-installed extension. */
    public function update(string $id): void
    {
        $extension = $this->requireExtension($id);

        if ($this->adapter instanceof RunsMigrations) {
            $this->adapter->runMigrations($extension);
        }

        $this->events->dispatch(new ExtensionUpdated($extension));
    }

    /**
     * Remove (uninstall): deactivate, unpublish the extension's assets, and forget its
     * management state (activation flag, version, settings). The extension's own
     * database tables are **preserved** — removing an extension must not destroy user
     * data; drop them deliberately with a migration if that's what you want.
     */
    public function remove(string $id): void
    {
        $extension = $this->requireExtension($id);

        $this->disable($id);

        if ($this->adapter instanceof PublishesAssets) {
            $this->adapter->unpublishAssets($extension);
        }

        $this->store->forget($id);
        $this->repository->forget();

        if (($hook = $this->resolveHook($extension)) !== null && method_exists($hook, 'removed')) {
            $hook->removed($extension);
        }

        $this->events->dispatch(new ExtensionRemoved($extension));
    }

    private function requireExtension(string $id): Extension
    {
        $extension = $this->repository->find($id);

        if (! $extension instanceof Extension) {
            throw new RuntimeException("Unknown extension [{$id}].");
        }

        return $extension;
    }

    /**
     * Resolve an extension's declared hook object (if any) from the container. The hook
     * is **duck-typed** — callers invoke whichever of `activated`/`deactivated`/
     * `installed`/`removed` exist — so a generated extension's hook needs no dependency
     * on this package. Implementing `Contracts\LifecycleHook` / `Contracts\InstallHook`
     * is an optional, type-safe way to declare those methods.
     */
    private function resolveHook(Extension $extension): ?object
    {
        if ($extension->hook === null || ! class_exists($extension->hook)) {
            return null;
        }

        $hook = $this->container->make($extension->hook);

        return is_object($hook) ? $hook : null;
    }
}
