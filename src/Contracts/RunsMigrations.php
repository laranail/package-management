<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Contracts;

use Simtabi\Laranail\Package\Management\Extension;

/**
 * Optional adapter capability: run an extension's own migrations
 * (`{path}/database/migrations`) on install/update. Adapters whose framework has no
 * migration runner simply don't implement it, and the manager skips migration steps.
 */
interface RunsMigrations
{
    public function runMigrations(Extension $extension): void;

    /** Roll back the extension's own migrations (used by the installer rollback + opt-in on remove). */
    public function rollbackMigrations(Extension $extension): void;
}
