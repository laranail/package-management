<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Events;

use Simtabi\Laranail\Package\Management\Extension;

/** Dispatched before an extension is activated. */
final readonly class ExtensionActivating
{
    public function __construct(public Extension $extension) {}
}
