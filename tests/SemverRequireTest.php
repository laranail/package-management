<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

use RuntimeException;
use Simtabi\Laranail\Package\Management\Facades\Extensions;

class SemverRequireTest extends TestCase
{
    private string $activationFile;

    protected function setUp(): void
    {
        $this->activationFile = sys_get_temp_dir() . '/laranail-pm-semver-' . getmypid() . '-' . uniqid() . '.json';
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
            'packages' => __DIR__ . '/Fixtures/semver/packages',
            'modules' => __DIR__ . '/Fixtures/semver/modules',
            'plugins' => __DIR__ . '/Fixtures/semver/plugins',
        ]);
        $app['config']->set('laranail.package-management.activation.file', $this->activationFile);
        $app['config']->set('laranail.package-management.cache.enabled', false);
    }

    public function test_the_reader_parses_the_map_require_form(): void
    {
        $needy = Extensions::find('needy');

        $this->assertNotNull($needy);
        $this->assertSame(['core'], $needy->require);                 // ids for topo/graph
        $this->assertSame(['core' => '^2.0'], $needy->requireVersions); // constraint map
    }

    public function test_a_satisfied_constraint_allows_activation(): void
    {
        Extensions::enable('core');   // core is 1.5.0
        Extensions::enable('happy');  // happy requires core ^1.0

        $this->assertTrue(is_extension_active('happy'));
    }

    public function test_an_unsatisfied_constraint_blocks_activation(): void
    {
        Extensions::enable('core'); // 1.5.0 does NOT satisfy needy's ^2.0

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/requires \[core\] \^2\.0, but \[1\.5\.0\]/');

        Extensions::enable('needy');
    }
}
