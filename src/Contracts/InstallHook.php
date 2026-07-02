<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Contracts;

use Simtabi\Laranail\Package\Management\Extension;

/**
 * Optional install-lifecycle handler. An extension's declared `hook` class may
 * implement this (in addition to, or instead of, {@see LifecycleHook}) to run logic
 * on install/remove — seed reference data, publish extras, tidy up on uninstall.
 * Invoked by the loader's install()/remove() flows.
 */
interface InstallHook
{
    public function installed(Extension $extension): void;

    public function removed(Extension $extension): void;
}
