<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

use Illuminate\Support\Facades\Event;
use Simtabi\Laranail\Package\Management\Events\ExtensionActivated;
use Simtabi\Laranail\Package\Management\Events\ExtensionDeactivated;
use Simtabi\Laranail\Package\Management\ExtensionManager;
use Simtabi\Laranail\Package\Management\Tests\Fixtures\RecordingHook;

class LifecycleTest extends TestCase
{
    private string $activationFile;

    protected function setUp(): void
    {
        $this->activationFile = sys_get_temp_dir() . '/laranail-pm-life-' . getmypid() . '-' . uniqid() . '.json';
        RecordingHook::$calls = [];
        parent::setUp();
    }

    protected function tearDown(): void
    {
        @unlink($this->activationFile);
        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('package-management.paths', [
            'packages' => __DIR__ . '/Fixtures/platform/packages',
            'modules' => __DIR__ . '/Fixtures/platform/modules',
            'plugins' => __DIR__ . '/Fixtures/platform/plugins',
        ]);
        $app['config']->set('package-management.activation.file', $this->activationFile);
        $app['config']->set('package-management.cache.enabled', false);
    }

    private function manager(): ExtensionManager
    {
        return $this->app->make(ExtensionManager::class);
    }

    public function test_declared_hook_is_invoked_on_activate_and_deactivate(): void
    {
        $manager = $this->manager();

        $manager->enable('alpha'); // alpha declares the RecordingHook
        $this->assertContains('activated:alpha', RecordingHook::$calls);

        $manager->disable('alpha');
        $this->assertContains('deactivated:alpha', RecordingHook::$calls);
    }

    public function test_extension_without_a_hook_activates_cleanly(): void
    {
        // acme/shop has no hook — must not error, and must fire no hook calls
        $this->manager()->enable('acme/shop');
        $this->assertSame([], RecordingHook::$calls);
    }

    public function test_events_are_dispatched_on_activate_and_deactivate(): void
    {
        Event::fake([ExtensionActivated::class, ExtensionDeactivated::class]);

        // rebuild the manager so it captures the faked dispatcher
        $this->app->forgetInstance(ExtensionManager::class);
        $manager = $this->app->make(ExtensionManager::class);

        $manager->enable('alpha');
        Event::assertDispatched(ExtensionActivated::class, static fn (ExtensionActivated $e): bool => $e->extension->id === 'alpha');

        $manager->disable('alpha');
        Event::assertDispatched(ExtensionDeactivated::class, static fn (ExtensionDeactivated $e): bool => $e->extension->id === 'alpha');
    }
}
