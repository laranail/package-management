<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Adapters;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Migrations\Migrator;
use Simtabi\Laranail\Package\Management\Adapters\Concerns\RegistersRuntimeAutoload;
use Simtabi\Laranail\Package\Management\Contracts\LoaderAdapter;
use Simtabi\Laranail\Package\Management\Contracts\RunsMigrations;
use Simtabi\Laranail\Package\Management\Extension;

/**
 * Laravel bridge. Runtime PSR-4 registration (via the shared trait) plus provider
 * registration through the full framework container — `$app->register()` handles
 * deferred providers, boot ordering and publishing. Missing provider classes are
 * skipped so a stale manifest never fatals the host boot. Also runs an extension's
 * own migrations on install/update.
 */
final class LaravelLoaderAdapter implements LoaderAdapter, RunsMigrations
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
}
