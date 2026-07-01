<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Contracts;

/**
 * Optional activation-store capability: record an extension's installed version (and
 * install timestamp) on install. Stores that don't track version (e.g. the file
 * store) simply don't implement it, and the manager skips the step.
 */
interface RecordsInstall
{
    public function recordInstall(string $id, ?string $version): void;
}
