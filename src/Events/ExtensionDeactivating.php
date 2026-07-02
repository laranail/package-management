<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Events;

use Simtabi\Laranail\Package\Management\Extension;

/** Dispatched before an extension is deactivated. */
final readonly class ExtensionDeactivating
{
    public function __construct(public Extension $extension) {}
}
