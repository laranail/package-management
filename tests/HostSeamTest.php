<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

use Simtabi\Laranail\Package\Management\Contracts\ContributesNavigation;
use Simtabi\Laranail\Package\Management\Extension;
use Simtabi\Laranail\Package\Management\Facades\Extensions;

class HostSeamTest extends TestCase
{
    private string $activationFile;

    protected function setUp(): void
    {
        $this->activationFile = sys_get_temp_dir() . '/laranail-pm-host-' . getmypid() . '-' . uniqid() . '.json';
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
            'packages' => __DIR__ . '/Fixtures/platform/packages',
            'modules' => __DIR__ . '/Fixtures/platform/modules',
            'plugins' => __DIR__ . '/Fixtures/platform/plugins',
        ]);
        $app['config']->set('laranail.package-management.activation.file', $this->activationFile);
        $app['config']->set('laranail.package-management.cache.enabled', false);
    }

    public function test_the_menu_manifest_field_is_read_and_exposed_on_the_vo(): void
    {
        $shop = Extensions::find('acme/shop');

        $this->assertNotNull($shop);
        $this->assertCount(1, $shop->menu);
        $this->assertSame('Shop', $shop->menu[0]['label']);
        $this->assertSame('/admin/shop', $shop->menu[0]['url']);
    }

    public function test_menu_round_trips_through_the_cache_payload(): void
    {
        $shop = Extensions::find('acme/shop');
        $this->assertNotNull($shop);

        $rehydrated = Extension::fromArray($shop->toArray());

        $this->assertSame($shop->menu, $rehydrated->menu);
    }

    public function test_a_host_can_collect_declarative_menu_entries_from_active_extensions(): void
    {
        // the HOST-side pattern the data seam enables — the loader is not involved
        Extensions::enable('acme/shop');

        $entries = [];
        foreach (Extensions::query()->active()->get() as $extension) {
            $entries = [...$entries, ...$extension->menu];
        }

        $this->assertContains('Shop', array_column($entries, 'label'));
    }

    public function test_the_contributes_navigation_contract_returns_entries(): void
    {
        // computed entries: the host resolves an extension's hook and, when it implements
        // the contract, merges its navigation() — the loader never calls this itself
        $hook = new class implements ContributesNavigation
        {
            public function navigation(Extension $extension): array
            {
                return [['label' => $extension->name . ' (dynamic)', 'url' => '/admin/' . $extension->slug()]];
            }
        };

        $shop = Extensions::find('acme/shop');
        $this->assertNotNull($shop);

        $nav = $hook->navigation($shop);

        $this->assertSame('Shop (dynamic)', $nav[0]['label']);
        $this->assertSame('/admin/acme-shop', $nav[0]['url']);
    }
}
