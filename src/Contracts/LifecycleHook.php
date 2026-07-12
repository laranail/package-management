<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Contracts;

use Simtabi\Laranail\Package\Management\Extension;

/**
 * Optional per-extension lifecycle handler. An extension names its hook FQCN in its
 * manifest (`hook`); the loader resolves it from the container and calls the matching
 * method on activation / deactivation — the place for an extension to seed data,
 * publish assets, clear caches, etc. Kept separate from provider registration so
 * hooks run only on the state transition, not on every boot.
 */
interface LifecycleHook
{
    public function activated(Extension $extension): void;

    public function deactivated(Extension $extension): void;
}
