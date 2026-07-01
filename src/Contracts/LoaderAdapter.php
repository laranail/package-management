<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Contracts;

use Simtabi\Laranail\Package\Management\Extension;

/**
 * Framework bridge. The loader core is framework-agnostic; everything
 * framework-specific (autoload registration, provider/bootstrap registration,
 * resource publishing) lives behind this interface. A Laravel adapter ships;
 * Lumen/Symfony adapters plug in the same way. See docs/extending.md.
 */
interface LoaderAdapter
{
    /** Register the extension's PSR-4 namespace on the runtime autoloader. */
    public function registerAutoload(Extension $extension): void;

    /** Register the extension's service provider(s) with the host, skipping missing classes. */
    public function registerProviders(Extension $extension): void;
}
