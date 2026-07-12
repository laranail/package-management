<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Simtabi\Laranail\Package\Management\Events\ExtensionInstalled;
use Simtabi\Laranail\Package\Management\ExtensionManager;
use Simtabi\Laranail\Package\Management\Tests\Fixtures\PlainRecordingHook;

class InstallTest extends TestCase
{
    use RefreshDatabase;

    private string $activationFile;

    private string $publicDir;

    protected function setUp(): void
    {
        $this->activationFile = sys_get_temp_dir() . '/laranail-pm-install-' . getmypid() . '-' . uniqid() . '.json';
        $this->publicDir = sys_get_temp_dir() . '/laranail-pm-public-' . getmypid() . '-' . uniqid();
        PlainRecordingHook::$calls = [];
        parent::setUp();
    }

    protected function tearDown(): void
    {
        @unlink($this->activationFile);
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
        $app['config']->set('laranail.package-management.activation.file', $this->activationFile);
        $app['config']->set('laranail.package-management.cache.enabled', false);

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

    public function test_install_runs_migrations_publishes_assets_and_activates(): void
    {
        $this->assertFalse(Schema::hasTable('migrated_items'));
        $this->assertFileDoesNotExist($this->publicDir . '/vendor/migrated/css/app.css');

        $this->manager()->install('migrated');

        $this->assertTrue(Schema::hasTable('migrated_items'), 'install should run the extension migrations');
        $this->assertFileExists(
            $this->publicDir . '/vendor/migrated/css/app.css',
            'install should publish the extension public assets',
        );
        $this->assertTrue(is_extension_active('migrated'), 'install should activate the extension');
    }

    public function test_install_invokes_the_plain_duck_typed_hook(): void
    {
        // Migrated declares PlainRecordingHook — an interface-free class (as the
        // scaffolder generates). The loader must still invoke it by duck-typing.
        $this->manager()->install('migrated');

        $this->assertContains('activated:migrated', PlainRecordingHook::$calls);
        $this->assertContains('installed:migrated', PlainRecordingHook::$calls);
    }

    public function test_update_is_idempotent(): void
    {
        $manager = $this->manager();
        $manager->install('migrated');

        // no pending migrations remain — running update again must not error
        $manager->update('migrated');

        $this->assertTrue(Schema::hasTable('migrated_items'));
    }

    public function test_install_dispatches_the_installed_event(): void
    {
        Event::fake([ExtensionInstalled::class]);
        $this->app->forgetInstance(ExtensionManager::class);

        $this->app->make(ExtensionManager::class)->install('migrated');

        Event::assertDispatched(ExtensionInstalled::class, static fn (ExtensionInstalled $e): bool => $e->extension->id === 'migrated');
    }
}
