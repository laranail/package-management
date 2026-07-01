<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Providers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Override;
use Simtabi\Laranail\Package\Management\Adapters\LaravelLoaderAdapter;
use Simtabi\Laranail\Package\Management\Commands\CacheExtensionsCommand;
use Simtabi\Laranail\Package\Management\Commands\DisableExtensionCommand;
use Simtabi\Laranail\Package\Management\Commands\DiscoverExtensionsCommand;
use Simtabi\Laranail\Package\Management\Commands\EnableExtensionCommand;
use Simtabi\Laranail\Package\Management\Commands\ListExtensionsCommand;
use Simtabi\Laranail\Package\Management\Contracts\ActivationStore;
use Simtabi\Laranail\Package\Management\Contracts\LoaderAdapter;
use Simtabi\Laranail\Package\Management\ExtensionManager;
use Simtabi\Laranail\Package\Management\ExtensionRepository;
use Simtabi\Laranail\Package\Management\Manifests\ManifestReader;
use Simtabi\Laranail\Package\Management\Stores\DatabaseActivationStore;
use Simtabi\Laranail\Package\Management\Stores\FileActivationStore;
use Simtabi\Laranail\Package\Management\Support\DependencyResolver;

/**
 * Entry point for laranail/package-management — the runtime loader. Binds the loader
 * pipeline, then on boot discovers + registers every active module/plugin.
 */
final class ManagementServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/package-management.php', 'package-management');

        $this->app->singleton(ManifestReader::class, static fn (): ManifestReader => new ManifestReader(new Filesystem));
        $this->app->singleton(DependencyResolver::class, static fn (): DependencyResolver => new DependencyResolver);

        $this->app->singleton(ActivationStore::class, static function (Application $app): ActivationStore {
            $activation = (array) config('package-management.activation', []);

            if (($activation['store'] ?? 'file') === 'database') {
                return new DatabaseActivationStore(
                    $app->make(ConnectionResolverInterface::class),
                    (string) ($activation['table'] ?? 'laranail_extension_states'),
                    isset($activation['connection']) ? (string) $activation['connection'] : null,
                );
            }

            return new FileActivationStore(new Filesystem, (string) ($activation['file'] ?? ''));
        });

        $this->app->singleton(LoaderAdapter::class, static fn (Application $app): LoaderAdapter => new LaravelLoaderAdapter($app));

        $this->app->singleton(ExtensionRepository::class, static function (Application $app): ExtensionRepository {
            $paths = (array) config('package-management.paths', []);
            $cache = (array) config('package-management.cache', []);
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

    public function boot(): void
    {
        // The database activation store needs its state table.
        if (config('package-management.activation.store') === 'database') {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        }

        // Register every active module/plugin (dependency order) into the host.
        $this->app->make(ExtensionManager::class)->boot();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/package-management.php' => config_path('package-management.php'),
            ], 'package-management-config');

            $this->publishes([
                __DIR__ . '/../../database/migrations' => database_path('migrations'),
            ], 'package-management-migrations');

            $this->commands([
                ListExtensionsCommand::class,
                EnableExtensionCommand::class,
                DisableExtensionCommand::class,
                DiscoverExtensionsCommand::class,
                CacheExtensionsCommand::class,
            ]);
        }
    }
}
