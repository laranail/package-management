<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Events;

use Simtabi\Laranail\Package\Management\Extension;

/** Dispatched before an extension is removed. */
final readonly class ExtensionRemoving
{
    public function __construct(public Extension $extension) {}
}
