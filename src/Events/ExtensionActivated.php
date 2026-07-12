<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Events;

use Simtabi\Laranail\Package\Management\Extension;

/** Dispatched after an extension is activated. */
final readonly class ExtensionActivated
{
    public function __construct(public Extension $extension) {}
}
