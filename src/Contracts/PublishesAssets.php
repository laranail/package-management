<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Contracts;

use Simtabi\Laranail\Package\Management\Extension;

/**
 * Optional adapter capability: publish an extension's bundled `public/` assets into
 * the host's public directory on install. Adapters whose framework has no public web
 * root simply don't implement it, and the manager skips the publish step.
 */
interface PublishesAssets
{
    public function publishAssets(Extension $extension): void;
}
