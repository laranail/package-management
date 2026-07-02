<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Providers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Override;
use Simtabi\Laranail\Package\Management\Actions\ActivateExtension;
use Simtabi\Laranail\Package\Management\Actions\DeactivateExtension;
use Simtabi\Laranail\Package\Management\Adapters\LaravelLoaderAdapter;
use Simtabi\Laranail\Package\Management\Commands\CacheExtensionsCommand;
use Simtabi\Laranail\Package\Management\Commands\DisableExtensionCommand;
use Simtabi\Laranail\Package\Management\Commands\DiscoverExtensionsCommand;
use Simtabi\Laranail\Package\Management\Commands\EnableExtensionCommand;
use Simtabi\Laranail\Package\Management\Commands\InstallExtensionCommand;
use Simtabi\Laranail\Package\Management\Commands\ListExtensionsCommand;
use Simtabi\Laranail\Package\Management\Commands\RemoveExtensionCommand;
use Simtabi\Laranail\Package\Management\Contracts\ActivationStore;
use Simtabi\Laranail\Package\Management\Contracts\ExtensionStateRepositoryInterface;
use Simtabi\Laranail\Package\Management\Contracts\LoaderAdapter;
use Simtabi\Laranail\Package\Management\ExtensionManager;
use Simtabi\Laranail\Package\Management\ExtensionRepository;
use Simtabi\Laranail\Package\Management\ExtensionStateManager;
use Simtabi\Laranail\Package\Management\Manifests\ManifestReader;
use Simtabi\Laranail\Package\Management\Repositories\EloquentExtensionStateRepository;
use Simtabi\Laranail\Package\Management\Services\ExtensionStateService;
use Simtabi\Laranail\Package\Management\Stores\EloquentActivationStore;
use Simtabi\Laranail\Package\Management\Stores\FileActivationStore;
use Simtabi\Laranail\Package\Management\Support\DependencyResolver;
use Simtabi\Laranail\Package\Tools\Package;
use Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider;

/**
 * Entry point for laranail/package-management — the runtime loader. Built on
 * laranail/package-tools: `configurePackage()` declares the package (namespaced config,
 * migrations, commands); `packageRegistered()` wires the loader + state subsystem;
 * `packageBooted()` discovers + registers every active module/plugin.
 */
final class ManagementServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        // vendor/package name → config merges under `config('laranail.package-management.*')`
        $package
            ->name('laranail/package-management')
            ->setPublishTagId('package-management')
            ->hasConfigFile()
            ->discoversMigrations()
            ->runsMigrations()
            ->hasCommands([
                ListExtensionsCommand::class,
                EnableExtensionCommand::class,
                DisableExtensionCommand::class,
                DiscoverExtensionsCommand::class,
                CacheExtensionsCommand::class,
                InstallExtensionCommand::class,
                RemoveExtensionCommand::class,
            ])
            ->hasAboutSection('Package Management', function (): array {
                $manager = $this->app->make(ExtensionManager::class);

                return [
                    'Discovered' => (string) count($manager->all()),
                    'Active' => (string) count($manager->active()),
                    'Modules' => (string) count($manager->modules()),
                    'Plugins' => (string) count($manager->plugins()),
                    'Store' => (string) config('laranail.package-management.activation.store', 'file'),
                ];
            });
    }

    #[Override]
    public function packageRegistered(): void
    {
        $this->app->singleton(ManifestReader::class, static fn (): ManifestReader => new ManifestReader(new Filesystem));
        $this->app->singleton(DependencyResolver::class, static fn (): DependencyResolver => new DependencyResolver);

        // Eloquent activation-state subsystem (Actions → Service → Repository → Model),
        // used by the database store + exposed via the ExtensionState facade.
        $this->app->bind(ExtensionStateRepositoryInterface::class, EloquentExtensionStateRepository::class);
        $this->app->singleton(ExtensionStateService::class);
        $this->app->singleton(ActivateExtension::class);
        $this->app->singleton(DeactivateExtension::class);
        $this->app->singleton(ExtensionStateManager::class);

        $this->app->singleton(ActivationStore::class, static function (Application $app): ActivationStore {
            $activation = (array) config('laranail.package-management.activation', []);

            if (($activation['store'] ?? 'file') === 'database') {
                return $app->make(EloquentActivationStore::class);
            }

            return new FileActivationStore(new Filesystem, (string) ($activation['file'] ?? ''));
        });

        $this->app->singleton(LoaderAdapter::class, static fn (Application $app): LoaderAdapter => new LaravelLoaderAdapter($app));

        $this->app->singleton(ExtensionRepository::class, static function (Application $app): ExtensionRepository {
            $paths = (array) config('laranail.package-management.paths', []);
            $cache = (array) config('laranail.package-management.cache', []);
            $cachePath = (string) ($cache['path'] ?? '');

            return new ExtensionRepository(
                new Filesystem,
                $app->make(ManifestReader::class),
                $app->make(ActivationStore::class),
                [
                    'package' => (string) ($paths['packages'] ?? ''),
                    'module' => (string) ($paths['modules'] ?? ''),
                    'plugin' => (string) ($paths['plugins'] ?? ''),
                ],
                (bool) ($cache['enabled'] ?? false),
                $cachePath === '' || str_starts_with($cachePath, DIRECTORY_SEPARATOR)
                    ? $cachePath
                    : $app->basePath($cachePath),
            );
        });

        $this->app->singleton(ExtensionManager::class, static fn (Application $app): ExtensionManager => new ExtensionManager(
            $app->make(ExtensionRepository::class),
            $app->make(DependencyResolver::class),
            $app->make(LoaderAdapter::class),
            $app->make(ActivationStore::class),
            $app->make(Dispatcher::class),
            $app,
        ));
    }

    #[Override]
    public function packageBooted(): void
    {
        // Register every active module/plugin (dependency order) into the host.
        $this->app->make(ExtensionManager::class)->boot();
    }
}
