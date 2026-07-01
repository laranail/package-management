<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Adapters;

use Composer\Autoload\ClassLoader;
use Illuminate\Contracts\Foundation\Application;
use Simtabi\Laranail\Package\Management\Contracts\LoaderAdapter;
use Simtabi\Laranail\Package\Management\Extension;

/**
 * Laravel bridge. Registers each extension's PSR-4 namespace on a runtime Composer
 * ClassLoader (so extensions load without a `composer dump`) and registers their
 * service providers with the container — skipping missing classes so a stale
 * manifest never fatals the host boot.
 */
final class LaravelLoaderAdapter implements LoaderAdapter
{
    private ?ClassLoader $loader = null;

    private bool $registered = false;

    public function __construct(private readonly Application $app) {}

    public function registerAutoload(Extension $extension): void
    {
        if ($extension->namespace === '') {
            return;
        }

        $this->loader ??= new ClassLoader;
        $this->loader->setPsr4($extension->namespace, $extension->sourcePath());

        if (! $this->registered) {
            $this->loader->register();
            $this->registered = true;
        }
    }

    public function registerProviders(Extension $extension): void
    {
        foreach ($extension->providers as $provider) {
            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        }
    }
}
