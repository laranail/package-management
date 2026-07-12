<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Simtabi\Laranail\Package\Management\Events\ExtensionRemoved;
use Simtabi\Laranail\Package\Management\ExtensionManager;
use Simtabi\Laranail\Package\Management\Models\ExtensionState;

class RemoveTest extends TestCase
{
    use RefreshDatabase;

    private string $publicDir;

    protected function setUp(): void
    {
        $this->publicDir = sys_get_temp_dir() . '/laranail-pm-rm-' . getmypid() . '-' . uniqid();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->publicDir);
        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app->usePublicPath($this->publicDir);

        $app['config']->set('laranail.package-management.paths', [
            'packages' => __DIR__ . '/Fixtures/install/packages',
            'modules' => __DIR__ . '/Fixtures/install/modules',
            'plugins' => __DIR__ . '/Fixtures/install/plugins',
        ]);
        $app['config']->set('laranail.package-management.cache.enabled', false);
        $app['config']->set('laranail.package-management.activation.store', 'database');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    private function manager(): ExtensionManager
    {
        return $this->app->make(ExtensionManager::class);
    }

    public function test_remove_deactivates_unpublishes_forgets_state_but_preserves_data(): void
    {
        $manager = $this->manager();
        $manager->install('migrated');

        // preconditions after install
        $this->assertTrue(is_extension_active('migrated'));
        $this->assertFileExists($this->publicDir . '/vendor/migrated/css/app.css');
        $this->assertNotNull(ExtensionState::query()->where('name', 'migrated')->first());
        $this->assertTrue(Schema::hasTable('migrated_items'));

        $manager->remove('migrated');

        $this->assertFalse(is_extension_active('migrated'), 'remove should deactivate');
        $this->assertFileDoesNotExist(
            $this->publicDir . '/vendor/migrated/css/app.css',
            'remove should unpublish assets',
        );
        $this->assertNull(
            ExtensionState::query()->where('name', 'migrated')->first(),
            'remove should forget the state row',
        );
        $this->assertTrue(
            Schema::hasTable('migrated_items'),
            'remove must PRESERVE the extension database tables (no data loss)',
        );
    }

    public function test_remove_dispatches_the_removed_event(): void
    {
        $this->manager()->install('migrated');

        Event::fake([ExtensionRemoved::class]);
        $this->app->forgetInstance(ExtensionManager::class);

        $this->app->make(ExtensionManager::class)->remove('migrated');

        Event::assertDispatched(ExtensionRemoved::class, static fn (ExtensionRemoved $e): bool => $e->extension->id === 'migrated');
    }
}
