<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Events;

use Simtabi\Laranail\Package\Management\Extension;

/** Dispatched before an extension's pending migrations are run (update). */
final readonly class ExtensionUpdating
{
    public function __construct(public Extension $extension) {}
}
