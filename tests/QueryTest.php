<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

use Simtabi\Laranail\Package\Management\Facades\Extensions;

class QueryTest extends TestCase
{
    private string $activationFile;

    protected function setUp(): void
    {
        $this->activationFile = sys_get_temp_dir() . '/laranail-pm-query-' . getmypid() . '-' . uniqid() . '.json';
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

    public function test_query_filters_by_role(): void
    {
        foreach (Extensions::query()->role('plugin')->get() as $extension) {
            $this->assertSame('plugin', $extension->role);
        }

        $this->assertContains('acme/shop', Extensions::query()->role('plugin')->ids());
    }

    public function test_query_filters_by_state(): void
    {
        $this->assertSame([], Extensions::query()->active()->get());  // nothing active yet

        Extensions::enable('acme/shop');

        $this->assertSame(['acme/shop'], Extensions::query()->active()->ids());
        $this->assertNotContains('acme/shop', Extensions::query()->inactive()->ids());
    }

    public function test_graph_and_dependents(): void
    {
        $graph = Extensions::graph();

        $this->assertArrayHasKey('acme/shop', $graph);
        $this->assertIsArray($graph['acme/shop']);

        // dependents() = reverse deps; acme/shop has no requirers in the fixture set
        $this->assertSame([], Extensions::dependents('acme/shop'));
    }
}
