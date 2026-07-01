<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Simtabi\Laranail\Package\Management\Contracts\ActivationStore;
use Simtabi\Laranail\Package\Management\Contracts\RecordsInstall;
use Simtabi\Laranail\Package\Management\ExtensionManager;
use Simtabi\Laranail\Package\Management\Models\ExtensionState;
use Simtabi\Laranail\Package\Management\Stores\EloquentActivationStore;

class EloquentActivationStoreTest extends TestCase
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

    public function test_database_store_is_the_eloquent_store(): void
    {
        $store = $this->store();
        $this->assertInstanceOf(EloquentActivationStore::class, $store);
        $this->assertInstanceOf(RecordsInstall::class, $store);
    }

    public function test_activate_deactivate_and_active_list(): void
    {
        $store = $this->store();

        $this->assertSame([], $store->active());

        $store->activate('alpha');
        $this->assertTrue($store->isActive('alpha'));
        $this->assertSame(['alpha'], $store->active());

        $store->deactivate('alpha');
        $this->assertFalse($store->isActive('alpha'));
    }

    public function test_manager_drives_the_store_end_to_end(): void
    {
        $manager = $this->app->make(ExtensionManager::class);

        $manager->enable('alpha');
        $manager->enable('acme/beta'); // requires alpha, now active
        $this->assertTrue(is_extension_active('acme/beta'));

        $this->expectException(RuntimeException::class); // reverse-dependency guard via the DB store
        $manager->disable('alpha');
    }

    public function test_install_records_version_and_installed_at(): void
    {
        $this->app->make(ExtensionManager::class)->install('alpha');

        $row = ExtensionState::query()->where('name', 'alpha')->first();
        $this->assertNotNull($row);
        $this->assertSame('1.0.0', $row->version); // alpha fixture version
        $this->assertNotNull($row->installed_at);
        $this->assertTrue($row->is_active);
    }
}
