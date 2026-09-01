<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Adapters;

use ReflectionClass;
use ReflectionMethod;
use Simtabi\Laranail\Package\Management\Adapters\Concerns\RegistersRuntimeAutoload;
use Simtabi\Laranail\Package\Management\Contracts\LoaderAdapter;
use Simtabi\Laranail\Package\Management\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Symfony bridge. Autoloading is shared with the other adapters (runtime PSR-4).
 *
 * Provider registration is a **runtime** service registration — Symfony compiles its
 * container at build time, so this is the runtime surface `Container::set()` supports
 * (a compiler pass / bundle is the build-time route for richer wiring; see
 * docs/tools/adapters.md). For each declared provider the adapter:
 *   - instantiates it, injecting the container when the constructor accepts an argument
 *     (else no-arg — backward compatible);
 *   - `set()`s the instance under its FQCN **and** under each interface it implements, so
 *     consumers can resolve by a stable interface;
 *   - calls an optional `register()` / `boot()` (duck-typed, container passed when the
 *     method accepts it), mirroring the Lumen adapter's runtime lifecycle seam.
 * Missing classes are skipped, and if the container is already compiled the step is a
 * no-op rather than a fatal, so a stale manifest never breaks boot.
 */
final class SymfonyLoaderAdapter implements LoaderAdapter
{
    use RegistersRuntimeAutoload;

    public function __construct(private readonly ContainerInterface $container) {}

    public function registerProviders(Extension $extension): void
    {
        if ($this->container instanceof ContainerBuilder && $this->container->isCompiled()) {
            return; // sealed at build time — register services via a CompilerPass instead
        }

        foreach ($extension->providers as $provider) {
            if (! class_exists($provider)) {
                continue;
            }

            $instance = $this->instantiate($provider);
            $this->container->set($provider, $instance);

            foreach (class_implements($provider) ?: [] as $interface) {
                $this->container->set($interface, $instance);
            }

            $this->invoke($instance, 'register');
            $this->invoke($instance, 'boot');
        }
    }

    /**
     * Inject the container when the constructor takes an argument; else construct no-arg.
     *
     * @param  class-string  $provider
     */
    private function instantiate(string $provider): object
    {
        $constructor = (new ReflectionClass($provider))->getConstructor();

        return $constructor !== null && $constructor->getNumberOfParameters() > 0
            ? new $provider($this->container)
            : new $provider;
    }

    /** Call an optional lifecycle method, passing the container when it accepts one. */
    private function invoke(object $instance, string $method): void
    {
        if (! method_exists($instance, $method)) {
            return;
        }

        if ((new ReflectionMethod($instance, $method))->getNumberOfParameters() > 0) {
            $instance->{$method}($this->container);

            return;
        }

        $instance->{$method}();
    }
}
