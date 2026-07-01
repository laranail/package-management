<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Adapters;

use Illuminate\Contracts\Foundation\Application;
use Simtabi\Laranail\Package\Management\Adapters\Concerns\RegistersRuntimeAutoload;
use Simtabi\Laranail\Package\Management\Contracts\LoaderAdapter;
use Simtabi\Laranail\Package\Management\Extension;

/**
 * Laravel bridge. Runtime PSR-4 registration (via the shared trait) plus provider
 * registration through the full framework container — `$app->register()` handles
 * deferred providers, boot ordering and publishing. Missing provider classes are
 * skipped so a stale manifest never fatals the host boot.
 */
final class LaravelLoaderAdapter implements LoaderAdapter
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
}
