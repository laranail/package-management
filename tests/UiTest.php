<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

class UiTest extends TestCase
{
    private string $activationFile;

    protected function setUp(): void
    {
        $this->activationFile = sys_get_temp_dir() . '/laranail-pm-ui-' . getmypid() . '-' . uniqid() . '.json';
        parent::setUp();
    }

    protected function tearDown(): void
    {
        @unlink($this->activationFile);
        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('laranail.package-management.paths', [
            'packages' => __DIR__ . '/Fixtures/platform/packages',
            'modules' => __DIR__ . '/Fixtures/platform/modules',
            'plugins' => __DIR__ . '/Fixtures/platform/plugins',
        ]);
        $app['config']->set('laranail.package-management.activation.file', $this->activationFile);
        $app['config']->set('laranail.package-management.cache.enabled', false);
        $app['config']->set('laranail.package-management.ui.enabled', true);
        $app['config']->set('laranail.package-management.ui.prefix', 'ext');
    }

    public function test_index_lists_discovered_extensions(): void
    {
        $this->get('/ext')
            ->assertOk()
            ->assertSee('alpha')
            ->assertSee('acme/beta');
    }

    public function test_enable_and_disable_via_the_ui(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->post('/ext/enable', ['id' => 'alpha'])->assertRedirect();
        $this->assertTrue(is_extension_active('alpha'));

        $this->post('/ext/disable', ['id' => 'alpha'])->assertRedirect();
        $this->assertFalse(is_extension_active('alpha'));
    }

    public function test_enable_failure_flashes_the_error(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        // acme/beta requires alpha (not active) — the guard message is flashed, not fatal
        $this->post('/ext/enable', ['id' => 'acme/beta'])
            ->assertRedirect()
            ->assertSessionHas('status', fn (string $s): bool => str_contains($s, 'requires'));

        $this->assertFalse(is_extension_active('acme/beta'));
    }
}
