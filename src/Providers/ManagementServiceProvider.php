<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Providers;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Override;
use Simtabi\Laranail\Package\Management\Actions\ActivateExtension;
use Simtabi\Laranail\Package\Management\Actions\DeactivateExtension;
use Simtabi\Laranail\Package\Management\Adapters\LaravelLoaderAdapter;
use Simtabi\Laranail\Package\Management\Commands\CacheExtensionsCommand;
use Simtabi\Laranail\Package\Management\Commands\DisableExtensionCommand;
use Simtabi\Laranail\Package\Management\Commands\DiscoverExtensionsCommand;
use Simtabi\Laranail\Package\Management\Commands\EnableExtensionCommand;
use Simtabi\Laranail\Package\Management\Commands\InstallExtensionCommand;
use Simtabi\Laranail\Package\Management\Commands\InstallFromVcsCommand;
use Simtabi\Laranail\Package\Management\Commands\ListExtensionsCommand;
use Simtabi\Laranail\Package\Management\Commands\RemoveExtensionCommand;
use Simtabi\Laranail\Package\Management\Commands\UpdateExtensionCommand;
use Simtabi\Laranail\Package\Management\Contracts\ActivationStore;
use Simtabi\Laranail\Package\Management\Contracts\ExtensionStateRepositoryInterface;
use Simtabi\Laranail\Package\Management\Contracts\LoaderAdapter;
use Simtabi\Laranail\Package\Management\Events\ExtensionActivated;
use Simtabi\Laranail\Package\Management\Events\ExtensionDeactivated;
use Simtabi\Laranail\Package\Management\Events\ExtensionInstalled;
use Simtabi\Laranail\Package\Management\Events\ExtensionRemoved;
use Simtabi\Laranail\Package\Management\ExtensionManager;
use Simtabi\Laranail\Package\Management\ExtensionRepository;
use Simtabi\Laranail\Package\Management\ExtensionStateManager;
use Simtabi\Laranail\Package\Management\Http\Controllers\ExtensionController;
use Simtabi\Laranail\Package\Management\Installer\ExtensionInstaller;
use Simtabi\Laranail\Package\Management\Installer\SourceDriverManager;
use Simtabi\Laranail\Package\Management\Listeners\FlushExtensionStateCache;
use Simtabi\Laranail\Package\Management\Manifests\ManifestReader;
use Simtabi\Laranail\Package\Management\Processing\ManifestPipeline;
use Simtabi\Laranail\Package\Management\Repositories\CachingExtensionStateRepository;
use Simtabi\Laranail\Package\Management\Repositories\EloquentExtensionStateRepository;
use Simtabi\Laranail\Package\Management\Services\ExtensionStateService;
use Simtabi\Laranail\Package\Management\Stores\EloquentActivationStore;
use Simtabi\Laranail\Package\Management\Stores\FileActivationStore;
use Simtabi\Laranail\Package\Management\Support\DependencyResolver;
use Simtabi\Laranail\Package\Tools\Package;
use Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider;
use Simtabi\Laranail\Package\Tools\Support\Definitions\AboutSectionDefinition;

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
            ->hasConfigFile('package-management')
            ->discoversMigrations()
            ->runsMigrations()
            ->hasViews('package-management')
            ->hasCommands([
                ListExtensionsCommand::class,
                EnableExtensionCommand::class,
                DisableExtensionCommand::class,
                DiscoverExtensionsCommand::class,
                CacheExtensionsCommand::class,
                InstallExtensionCommand::class,
                RemoveExtensionCommand::class,
                UpdateExtensionCommand::class,
                InstallFromVcsCommand::class,
            ])
            ->hasAboutSection(
                AboutSectionDefinition::make('Package Management')
                    ->fieldsUsing(function (): array {
                        $manager = $this->app->make(ExtensionManager::class);

                        return [
                            'Discovered' => (string) count($manager->all()),
                            'Active' => (string) count($manager->active()),
                            'Modules' => (string) count($manager->modules()),
                            'Plugins' => (string) count($manager->plugins()),
                            'Store' => (string) config('laranail.package-management.activation.store', 'file'),
                        ];
                    }),
            );
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

        $this->app->singleton(ManifestPipeline::class);

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
                $app->make(ManifestPipeline::class),
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

        // VCS installer (source drivers + orchestration)
        $this->app->singleton(SourceDriverManager::class);
        $this->app->singleton(ExtensionInstaller::class);
    }

    #[Override]
    public function packageBooted(): void
    {
        // Decorate the state repository with the caching layer BEFORE anything resolves it
        // (config is available here — after any host getEnvironmentSetUp override).
        if ((bool) config('laranail.package-management.activation.cache')) {
            $this->app->extend(
                ExtensionStateRepositoryInterface::class,
                static fn (ExtensionStateRepositoryInterface $repo, Application $app): ExtensionStateRepositoryInterface => new CachingExtensionStateRepository($repo, $app->make(CacheFactory::class)->store()),
            );
        }

        // Register every active module/plugin (dependency order) into the host.
        $this->app->make(ExtensionManager::class)->boot();

        if ((bool) config('laranail.package-management.activation.cache')) {
            Event::listen([
                ExtensionActivated::class,
                ExtensionDeactivated::class,
                ExtensionInstalled::class,
                ExtensionRemoved::class,
            ], FlushExtensionStateCache::class);
        }

        if ((bool) config('laranail.package-management.ui.enabled', false)) {
            $this->registerUiRoutes();
        }
    }

    /** The opt-in management UI routes (config `ui.*`). */
    private function registerUiRoutes(): void
    {
        Route::middleware((array) config('laranail.package-management.ui.middleware', ['web']))
            ->prefix((string) config('laranail.package-management.ui.prefix', 'laranail/extensions'))
            ->group(function (): void {
                Route::get('/', [ExtensionController::class, 'index'])->name('laranail.extensions.index');
                Route::post('/enable', [ExtensionController::class, 'enable'])->name('laranail.extensions.enable');
                Route::post('/disable', [ExtensionController::class, 'disable'])->name('laranail.extensions.disable');
                Route::post('/install', [ExtensionController::class, 'install'])->name('laranail.extensions.install');
                Route::post('/update', [ExtensionController::class, 'update'])->name('laranail.extensions.update');
                Route::post('/remove', [ExtensionController::class, 'remove'])->name('laranail.extensions.remove');
                Route::post('/install-from', [ExtensionController::class, 'installFrom'])->name('laranail.extensions.install-from');
            });
    }
}
