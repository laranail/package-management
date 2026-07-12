<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests\Integration;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Simtabi\Laranail\Package\Management\Facades\Extensions;
use Simtabi\Laranail\Package\Management\Tests\TestCase;

/**
 * Proves the package is drop-in reusable: with ONLY its own service provider registered
 * (no other laranail package at runtime), a freshly-dropped extension is discovered and
 * activatable, using a fully configured (custom) state table.
 */
class DropInReusabilityTest extends TestCase
{
    use RefreshDatabase;

    private string $platform;

    protected function setUp(): void
    {
        $this->platform = sys_get_temp_dir() . '/laranail-pm-dropin-' . getmypid() . '-' . uniqid();

        // drop a brand-new module into the platform BEFORE the app boots
        $dir = $this->platform . '/modules/Sample';
        @mkdir($dir, 0777, true);
        file_put_contents($dir . '/module.json', (string) json_encode([
            'name' => 'Sample', 'alias' => 'sample', 'providers' => [],
        ]));

        parent::setUp();
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->platform);
        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('laranail.package-management.paths', [
            'packages' => $this->platform . '/packages',
            'modules' => $this->platform . '/modules',
            'plugins' => $this->platform . '/plugins',
        ]);
        $app['config']->set('laranail.package-management.cache.enabled', false);
        $app['config']->set('laranail.package-management.activation.store', 'database');
        $app['config']->set('laranail.package-management.activation.table', 'custom_ext_states'); // configurable
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]);
    }

    public function test_a_dropped_extension_is_discovered_activated_and_recorded_in_the_configured_table(): void
    {
        // discovery — only ManagementServiceProvider is registered (see getPackageProviders)
        $this->assertNotNull(Extensions::find('sample'));
        $this->assertSame('module', Extensions::find('sample')?->role);

        // activation lifecycle against the DB store
        Extensions::install('sample');
        $this->assertTrue(is_extension_active('sample'));

        // the configurable table was created + used (drop-in reusability)
        $this->assertTrue(Schema::hasTable('custom_ext_states'));
        $this->assertDatabaseHas('custom_ext_states', ['name' => 'sample', 'is_active' => true]);
    }
}
