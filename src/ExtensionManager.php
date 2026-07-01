<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management;

use RuntimeException;
use Simtabi\Laranail\Package\Management\Contracts\ActivationStore;
use Simtabi\Laranail\Package\Management\Contracts\LoaderAdapter;
use Simtabi\Laranail\Package\Management\Support\DependencyResolver;

/**
 * Orchestrates the runtime loader: registers active modules/plugins in dependency
 * order and drives the activation lifecycle. The public runtime API behind the
 * `Extensions` facade + helpers.
 */
final class ExtensionManager
{
    public function __construct(
        private readonly ExtensionRepository $repository,
        private readonly DependencyResolver $resolver,
        private readonly LoaderAdapter $adapter,
        private readonly ActivationStore $store,
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
        $extension = $this->repository->find($id);

        if ($extension === null) {
            throw new RuntimeException("Unknown extension [{$id}].");
        }

        foreach ($extension->require as $dependency) {
            if (! $this->store->isActive($dependency)) {
                throw new RuntimeException("Extension [{$id}] requires [{$dependency}], which is not active.");
            }
        }

        $this->store->activate($id);
        $this->repository->forget();
    }

    public function disable(string $id): void
    {
        foreach ($this->repository->active() as $extension) {
            if (in_array($id, $extension->require, true)) {
                throw new RuntimeException("Cannot disable [{$id}]: [{$extension->id}] requires it.");
            }
        }

        $this->store->deactivate($id);
        $this->repository->forget();
    }
}
