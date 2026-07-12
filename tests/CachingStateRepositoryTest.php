<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Simtabi\Laranail\Package\Management\Contracts\ExtensionStateRepositoryInterface;
use Simtabi\Laranail\Package\Management\Repositories\CachingExtensionStateRepository;

class CachingStateRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('laranail.package-management.activation.store', 'database');
        $app['config']->set('laranail.package-management.activation.cache', true);
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]);
    }

    public function test_the_state_repository_is_decorated_when_cache_is_enabled(): void
    {
        $this->assertInstanceOf(
            CachingExtensionStateRepository::class,
            $this->app->make(ExtensionStateRepositoryInterface::class),
        );
    }

    public function test_active_names_are_cached_and_a_write_flushes_the_cache(): void
    {
        $repo = $this->app->make(ExtensionStateRepositoryInterface::class);

        $repo->activeNames();
        $this->assertTrue(Cache::has(CachingExtensionStateRepository::CACHE_KEY)); // read warms the cache

        $repo->markActive('acme/blog');
        $this->assertFalse(Cache::has(CachingExtensionStateRepository::CACHE_KEY)); // write flushed it

        $this->assertSame(['acme/blog'], $repo->activeNames()); // re-read reflects the write
    }
}
