<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Adapters;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\Package\Management\Adapters\Concerns\RegistersRuntimeAutoload;
use Simtabi\Laranail\Package\Management\Contracts\LoaderAdapter;
use Simtabi\Laranail\Package\Management\Contracts\PublishesAssets;
use Simtabi\Laranail\Package\Management\Contracts\RunsMigrations;
use Simtabi\Laranail\Package\Management\Extension;

/**
 * Laravel bridge. Runtime PSR-4 registration (via the shared trait) plus provider
 * registration through the full framework container — `$app->register()` handles
 * deferred providers, boot ordering and publishing. Missing provider classes are
 * skipped so a stale manifest never fatals the host boot. Also runs an extension's
 * own migrations and publishes its `public/` assets on install.
 */
final class LaravelLoaderAdapter implements LoaderAdapter, PublishesAssets, RunsMigrations
{
    use RegistersRuntimeAutoload;

    public function __construct(private readonly Application $app) {}

    public function registerProviders(Extension $extension): void
    {
        foreach ($extension->providers as $provider) {
            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        }
    }

    public function runMigrations(Extension $extension): void
    {
        $path = rtrim($extension->path, DIRECTORY_SEPARATOR) . '/database/migrations';

        if (! is_dir($path)) {
            return;
        }

        $migrator = $this->app->make('migrator');

        if (! $migrator instanceof Migrator) {
            return;
        }

        if (! $migrator->repositoryExists()) {
            $migrator->getRepository()->createRepository();
        }

        $migrator->run([$path]);
    }

    public function rollbackMigrations(Extension $extension): void
    {
        $path = rtrim($extension->path, DIRECTORY_SEPARATOR) . '/database/migrations';

        if (! is_dir($path)) {
            return;
        }

        $migrator = $this->app->make('migrator');

        if (! $migrator instanceof Migrator || ! $migrator->repositoryExists()) {
            return;
        }

        // Rolls back the last batch resolved from this path. During an install rollback the
        // extension's migrations ARE the last batch, so this cleanly undoes them.
        $migrator->rollback([$path]);
    }

    public function publishAssets(Extension $extension): void
    {
        $source = rtrim($extension->path, DIRECTORY_SEPARATOR) . '/public';

        if (! is_dir($source)) {
            return;
        }

        (new Filesystem)->copyDirectory($source, public_path('vendor/' . $extension->slug()));
    }

    public function unpublishAssets(Extension $extension): void
    {
        (new Filesystem)->deleteDirectory(public_path('vendor/' . $extension->slug()));
    }
}
