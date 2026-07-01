<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Simtabi\Laranail\Package\Management\Contracts\ActivationStore;
use Simtabi\Laranail\Package\Management\ExtensionManager;
use Simtabi\Laranail\Package\Management\Stores\DatabaseActivationStore;

class DatabaseActivationStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('package-management.paths', [
            'packages' => __DIR__ . '/Fixtures/platform/packages',
            'modules' => __DIR__ . '/Fixtures/platform/modules',
            'plugins' => __DIR__ . '/Fixtures/platform/plugins',
        ]);
        $app['config']->set('package-management.cache.enabled', false);
        $app['config']->set('package-management.activation.store', 'database');

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    private function store(): ActivationStore
    {
        return $this->app->make(ActivationStore::class);
    }

    public function test_the_database_store_is_bound_when_configured(): void
    {
        $this->assertInstanceOf(DatabaseActivationStore::class, $this->store());
    }

    public function test_activate_deactivate_and_active_list(): void
    {
        $store = $this->store();

        $this->assertSame([], $store->active());

        $store->activate('alpha');
        $this->assertTrue($store->isActive('alpha'));
        $this->assertSame(['alpha'], $store->active());

        // idempotent + updateOrInsert path
        $store->activate('alpha');
        $this->assertSame(['alpha'], $store->active());

        $store->deactivate('alpha');
        $this->assertFalse($store->isActive('alpha'));
        $this->assertSame([], $store->active());
    }

    public function test_manager_drives_the_database_store_end_to_end(): void
    {
        $manager = $this->app->make(ExtensionManager::class);

        $manager->enable('alpha');
        $manager->enable('acme/beta'); // requires alpha, now active
        $this->assertTrue(is_extension_active('acme/beta'));

        // reverse-dependency guard still holds through the DB store
        $this->expectException(RuntimeException::class);
        $manager->disable('alpha');
    }
}
