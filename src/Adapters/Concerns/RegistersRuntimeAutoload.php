<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Adapters\Concerns;

use Composer\Autoload\ClassLoader;
use Simtabi\Laranail\Package\Management\Extension;

/**
 * Runtime PSR-4 registration shared by every LoaderAdapter — it's framework-agnostic
 * (a Composer ClassLoader registered once, accumulating each extension's namespace),
 * so autoloading works without a `composer dump` on any framework.
 */
trait RegistersRuntimeAutoload
{
    private ?ClassLoader $runtimeLoader = null;

    private bool $runtimeLoaderRegistered = false;

    public function registerAutoload(Extension $extension): void
    {
        if ($extension->namespace === '') {
            return;
        }

        $this->runtimeLoader ??= new ClassLoader;
        $this->runtimeLoader->setPsr4($extension->namespace, $extension->sourcePath());

        if (! $this->runtimeLoaderRegistered) {
            $this->runtimeLoader->register();
            $this->runtimeLoaderRegistered = true;
        }
    }
}
