<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Entry point for laranail/package-management.
 *
 * Boots the runtime loader: discover extensions under the configured platform
 * paths, resolve their dependencies, register their autoloading + service
 * providers, and wire their backend/frontend into the host app. The discovery +
 * registration pipeline is added in the core-loader phase (B3); this skeleton
 * wires configuration + publishing.
 */
final class ManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/package-management.php', 'package-management');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/package-management.php' => config_path('package-management.php'),
            ], 'package-management-config');
        }

        // Discovery → dependency resolution → autoload + provider registration →
        // backend/frontend wiring is implemented in the core-loader phase.
    }
}
