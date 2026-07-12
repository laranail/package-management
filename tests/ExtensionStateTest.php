<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Simtabi\Laranail\Package\Management\Contracts\ExtensionStateRepositoryInterface;
use Simtabi\Laranail\Package\Management\Facades\ExtensionState as ExtensionStateFacade;
use Simtabi\Laranail\Package\Management\Models\ExtensionState;

class ExtensionStateTest extends TestCase
{
    use RefreshDatabase;

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('laranail.package-management.activation.store', 'database');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    public function test_model_factory_casts_and_active_scope(): void
    {
        ExtensionState::factory()->create(['name' => 'alpha']);
        ExtensionState::factory()->active()->create(['name' => 'beta']);

        $this->assertSame(2, ExtensionState::query()->count());
        $this->assertSame(['beta'], ExtensionState::query()->active()->pluck('name')->all());

        $beta = ExtensionState::query()->where('name', 'beta')->firstOrFail();
        $this->assertTrue($beta->is_active);                 // bool cast
        $this->assertNotNull($beta->activated_at);           // datetime cast
        $this->assertIsArray($beta->settings);               // array cast
    }

    public function test_repository_full_crud(): void
    {
        $repo = $this->app->make(ExtensionStateRepositoryInterface::class);

        $this->assertSame([], $repo->activeNames());

        $repo->markActive('shop');
        $this->assertTrue($repo->isActive('shop'));
        $this->assertSame(['shop'], $repo->activeNames());

        $state = $repo->find('shop');
        $this->assertNotNull($state?->installed_at); // set on first activation
        $this->assertNotNull($state?->activated_at);

        $repo->recordInstall('shop', '2.1.0');
        $this->assertSame('2.1.0', $repo->find('shop')?->version);

        $repo->putSettings('shop', ['theme' => 'dark']);
        $this->assertSame(['theme' => 'dark'], $repo->settings('shop'));

        $repo->markInactive('shop');
        $this->assertFalse($repo->isActive('shop'));
        $this->assertNull($repo->find('shop')?->activated_at);
    }

    public function test_facade_chain_activate_deactivate(): void
    {
        // Facade → Manager → ActivateExtension (DB::transaction) → Service → Repository → Model
        ExtensionStateFacade::activate('blog');

        $this->assertTrue(ExtensionStateFacade::isActive('blog'));
        $row = ExtensionState::query()->where('name', 'blog')->firstOrFail();
        $this->assertTrue($row->is_active);
        $this->assertNotNull($row->activated_at);

        ExtensionStateFacade::deactivate('blog');
        $this->assertFalse(ExtensionStateFacade::isActive('blog'));
    }

    public function test_facade_settings_round_trip(): void
    {
        ExtensionStateFacade::putSettings('blog', ['per_page' => 15]);
        $this->assertSame(['per_page' => 15], ExtensionStateFacade::settings('blog'));
    }
}
