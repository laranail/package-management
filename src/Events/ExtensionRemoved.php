<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Events;

use Simtabi\Laranail\Package\Management\Extension;

/** Dispatched after an extension is removed (deactivated + assets unpublished + state forgotten). */
final readonly class ExtensionRemoved
{
    public function __construct(public Extension $extension) {}
}
