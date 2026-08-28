<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

use Simtabi\Laranail\Package\Management\Providers\ManagementServiceProvider;
use Simtabi\Laranail\Package\Tools\Testing\IsolatedTestCase;

abstract class TestCase extends IsolatedTestCase
{
    protected function getPackageProviders($app): array
    {
        return [ManagementServiceProvider::class];
    }
}
