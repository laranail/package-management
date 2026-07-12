<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Events;

use Simtabi\Laranail\Package\Management\Extension;

/** Dispatched after an extension is deactivated. */
final readonly class ExtensionDeactivated
{
    public function __construct(public Extension $extension) {}
}
