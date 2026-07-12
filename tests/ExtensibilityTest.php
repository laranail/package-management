<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

use Closure;
use Illuminate\Support\Facades\Event;
use Simtabi\Laranail\Package\Management\Events\ExtensionActivating;
use Simtabi\Laranail\Package\Management\Events\ExtensionInstalled;
use Simtabi\Laranail\Package\Management\Events\ExtensionInstalling;
use Simtabi\Laranail\Package\Management\Extension;
use Simtabi\Laranail\Package\Management\ExtensionManager;
use Simtabi\Laranail\Package\Management\Facades\Extensions;

class ExtensibilityTest extends TestCase
{
    private string $activationFile;

    protected function setUp(): void
    {
        $this->activationFile = sys_get_temp_dir() . '/laranail-pm-ext-' . getmypid() . '-' . uniqid() . '.json';
        parent::setUp();
    }

    protected function tearDown(): void
    {
        ExtensionManager::flushMacros();
        @unlink($this->activationFile);
        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('laranail.package-management.paths', [
            'packages' => __DIR__ . '/Fixtures/platform/packages',
            'modules' => __DIR__ . '/Fixtures/platform/modules',
            'plugins' => __DIR__ . '/Fixtures/platform/plugins',
        ]);
        $app['config']->set('laranail.package-management.activation.file', $this->activationFile);
        $app['config']->set('laranail.package-management.cache.enabled', false);
    }

    public function test_the_manager_is_macroable(): void
    {
        ExtensionManager::macro('activeCount', function (): int {
            /** @var ExtensionManager $this */
            return count($this->active());
        });

        $this->assertIsInt(Extensions::activeCount());
    }

    public function test_pipe_enriches_extensions_at_discovery(): void
    {
        // a runtime stage bumps every discovered extension's priority to 99
        Extensions::pipe(fn (Extension $e, Closure $next): Extension => $next(
            Extension::fromArray(['priority' => 99] + $e->toArray()),
        ));

        // boot() already memoized a scan; rediscover so the new stage is applied
        $this->app->make(ExtensionManager::class)->discover();

        $shop = null;
        foreach (Extensions::all() as $extension) {
            if ($extension->id === 'acme/shop') {
                $shop = $extension;
            }
        }

        $this->assertNotNull($shop);
        $this->assertSame(99, $shop->priority);
    }

    public function test_lifecycle_fires_present_tense_events(): void
    {
        $fired = [];
        foreach ([ExtensionInstalling::class, ExtensionActivating::class, ExtensionInstalled::class] as $event) {
            Event::listen($event, function (object $e) use (&$fired): void {
                $fired[] = $e::class;
            });
        }

        Extensions::install('acme/shop');

        $this->assertContains(ExtensionInstalling::class, $fired);
        $this->assertContains(ExtensionActivating::class, $fired);
        $this->assertContains(ExtensionInstalled::class, $fired);
    }

    public function test_the_facade_can_be_spied(): void
    {
        Extensions::spy();

        Extensions::all();

        Extensions::shouldHaveReceived('all');
    }
}
