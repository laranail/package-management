<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

use Simtabi\Laranail\Package\Management\Contracts\ActivationStore;

/**
 * The database store must degrade gracefully before its table is migrated — the
 * provider reads activation during boot(), which on a fresh install runs before
 * `migrate`. No RefreshDatabase here, so the state table never exists.
 */
class ExtensionStateDegradationTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('package-management.activation.store', 'database');
        $app['config']->set('package-management.cache.enabled', false);
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    public function test_reads_return_empty_when_the_table_is_absent(): void
    {
        $store = $this->app->make(ActivationStore::class);

        // no fatal, empty results — the app already booted with this store above
        $this->assertSame([], $store->active());
        $this->assertFalse($store->isActive('anything'));
    }
}
