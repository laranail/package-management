<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Adapters;

use Simtabi\Laranail\Package\Management\Adapters\Concerns\RegistersRuntimeAutoload;
use Simtabi\Laranail\Package\Management\Contracts\LoaderAdapter;
use Simtabi\Laranail\Package\Management\Extension;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Symfony bridge. Autoloading is shared with the other adapters (runtime PSR-4).
 *
 * Provider registration differs by design: Symfony compiles its container at build
 * time, so there are no runtime "service providers" to boot. Instead each provider
 * class is instantiated and **set into the container as a service instance** under its
 * FQCN — the runtime surface Symfony's `Container::set()` supports (a compiler pass /
 * bundle is the build-time route for richer wiring). Providers must be no-arg
 * constructible; missing classes are skipped so a stale manifest never fatals.
 */
final class SymfonyLoaderAdapter implements LoaderAdapter
{
    use RegistersRuntimeAutoload;

    public function __construct(private readonly ContainerInterface $container) {}

    public function registerProviders(Extension $extension): void
    {
        foreach ($extension->providers as $provider) {
            if (! class_exists($provider)) {
                continue;
            }

            $this->container->set($provider, new $provider);
        }
    }
}
