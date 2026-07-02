<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

use RuntimeException;
use Simtabi\Laranail\Package\Management\Extension;
use Simtabi\Laranail\Package\Management\ExtensionManager;
use Simtabi\Laranail\Package\Management\Support\DependencyResolver;

class LoaderTest extends TestCase
{
    private string $activationFile;

    protected function setUp(): void
    {
        $this->activationFile = sys_get_temp_dir() . '/laranail-pm-' . getmypid() . '-' . uniqid() . '.json';
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

    private function manager(): ExtensionManager
    {
        return $this->app->make(ExtensionManager::class);
    }

    public function test_discovers_extensions_with_correct_roles(): void
    {
        $all = $this->manager()->all();
        $byId = [];
        foreach ($all as $e) {
            $byId[$e->id] = $e;
        }

        $this->assertArrayHasKey('alpha', $byId);
        $this->assertArrayHasKey('acme/beta', $byId);
        $this->assertSame('module', $byId['alpha']->role);
        $this->assertSame('plugin', $byId['acme/beta']->role);
    }

    public function test_manifest_reader_maps_namespace_and_require(): void
    {
        $beta = $this->manager()->find('acme/beta');

        $this->assertInstanceOf(Extension::class, $beta);
        $this->assertSame('Acme\\Beta\\', $beta->namespace);
        $this->assertSame(['alpha'], $beta->require);
        $this->assertStringContainsString('Acme\\Beta\\Providers\\BetaServiceProvider', $beta->providers[0]);

        // module namespace derived from its provider (drops Providers\Xxx)
        $alpha = $this->manager()->find('alpha');
        $this->assertSame('Fake\\Alpha\\', $alpha->namespace);
    }

    public function test_enable_requires_active_dependency(): void
    {
        $this->expectException(RuntimeException::class);
        $this->manager()->enable('acme/beta'); // alpha not active yet
    }

    public function test_activation_and_reverse_dependency_guard(): void
    {
        $m = $this->manager();
        $m->enable('alpha');
        $m->enable('acme/beta'); // now allowed

        $this->assertTrue(is_extension_active('alpha'));
        $this->assertTrue(is_extension_active('acme/beta'));

        try {
            $m->disable('alpha'); // beta still requires it
            $this->fail('Expected a reverse-dependency guard.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('requires it', $e->getMessage());
        }

        $m->disable('acme/beta');
        $m->disable('alpha'); // now allowed
        $this->assertFalse(is_extension_active('alpha'));
    }

    public function test_dependency_resolution_orders_dependencies_first(): void
    {
        $all = $this->manager()->all();
        $sorted = (new DependencyResolver)->sort($all);
        $order = array_map(static fn (Extension $e): string => $e->id, $sorted);

        $this->assertLessThan(
            array_search('acme/beta', $order, true),
            array_search('alpha', $order, true),
            'alpha must sort before acme/beta (which requires it).',
        );
    }

    /**
     * B4 cross-package contract: the loader reads a plugin.json in the exact shape
     * laranail/package-scaffolder emits (full field set per docs/manifests.md).
     */
    public function test_reads_a_scaffolder_shaped_plugin_manifest(): void
    {
        $shop = $this->manager()->find('acme/shop');

        $this->assertInstanceOf(Extension::class, $shop);
        $this->assertSame('plugin', $shop->role);
        $this->assertSame('Shop', $shop->name);
        $this->assertSame('Acme\\Shop\\', $shop->namespace);
        $this->assertSame('Acme\\Shop\\Providers\\ShopServiceProvider', $shop->providers[0]);
        $this->assertSame('1.0.0', $shop->version);
        // manifest fields read into the VO (G2): priority / type / minimum_core_version
        $this->assertSame(5, $shop->priority);
        $this->assertSame('plugin', $shop->type);
        $this->assertSame('1.0.0', $shop->minimumCoreVersion);
    }

    public function test_boot_is_safe_when_provider_classes_are_missing(): void
    {
        $this->manager()->enable('alpha'); // provider class does not exist
        // booting must not fatal — missing provider classes are skipped
        $this->manager()->boot();
        $this->assertTrue(true);
    }
}
