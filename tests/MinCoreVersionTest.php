<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

use RuntimeException;
use Simtabi\Laranail\Package\Management\ExtensionManager;

class MinCoreVersionTest extends TestCase
{
    private string $activationFile;

    protected function setUp(): void
    {
        $this->activationFile = sys_get_temp_dir() . '/laranail-pm-mcv-' . getmypid() . '-' . uniqid() . '.json';
        parent::setUp();
    }

    protected function tearDown(): void
    {
        @unlink($this->activationFile);
        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('laranail.package-management.paths', [
            'packages' => __DIR__ . '/Fixtures/incompatible/packages',
            'modules' => __DIR__ . '/Fixtures/incompatible/modules',
            'plugins' => __DIR__ . '/Fixtures/incompatible/plugins',
        ]);
        $app['config']->set('laranail.package-management.activation.file', $this->activationFile);
        $app['config']->set('laranail.package-management.cache.enabled', false);
    }

    public function test_enable_is_rejected_when_minimum_core_version_exceeds_the_running_version(): void
    {
        // acme/future declares minimum_core_version 9.9.9; the loader runs VERSION 1.0.0
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/requires package-management >= 9\.9\.9/');

        $this->app->make(ExtensionManager::class)->enable('acme/future');
    }
}
