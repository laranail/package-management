<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Adapters;

use Illuminate\Contracts\Container\Container;
use Simtabi\Laranail\Package\Management\Adapters\Concerns\RegistersRuntimeAutoload;
use Simtabi\Laranail\Package\Management\Contracts\LoaderAdapter;
use Simtabi\Laranail\Package\Management\Extension;

/**
 * Lumen (and bare-container) bridge. Autoloading is shared with the Laravel adapter;
 * provider registration is done against the plain container contract so it works
 * without a full Foundation application: it uses Lumen's `Application::register()`
 * when present, otherwise instantiates the provider and calls `register()` + `boot()`
 * itself (booting through the container so `boot()` dependencies are injected).
 *
 * Lumen has no package auto-discovery, so bind this over the default adapter:
 *   $app->singleton(LoaderAdapter::class, fn ($app) => new LumenLoaderAdapter($app));
 */
final class LumenLoaderAdapter implements LoaderAdapter
{
    use RegistersRuntimeAutoload;

    public function __construct(private readonly Container $app) {}

    public function registerProviders(Extension $extension): void
    {
        foreach ($extension->providers as $provider) {
            if (! class_exists($provider)) {
                continue;
            }

            // Lumen's Application exposes register(); prefer it when available.
            if (method_exists($this->app, 'register')) {
                $this->app->register($provider);

                continue;
            }

            $instance = new $provider($this->app);

            if (method_exists($instance, 'register')) {
                $instance->register();
            }

            if (method_exists($instance, 'boot')) {
                $this->app->call([$instance, 'boot']);
            }
        }
    }
}
