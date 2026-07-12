<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Events;

use Simtabi\Laranail\Package\Management\Extension;

/** Dispatched after an extension's pending migrations are run (update). */
final readonly class ExtensionUpdated
{
    public function __construct(public Extension $extension) {}
}
