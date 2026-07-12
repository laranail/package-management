<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Simtabi\Laranail\Package\Management\Providers\ManagementServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [ManagementServiceProvider::class];
    }
}
