<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Phar;
use PharData;

class UiInstallFromTest extends TestCase
{
    use RefreshDatabase;

    private string $platform;

    /** @var list<string> */
    private array $tarballs = [];

    protected function setUp(): void
    {
        $this->platform = sys_get_temp_dir() . '/laranail-pm-uivcs-' . getmypid() . '-' . uniqid();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->platform);
        foreach ($this->tarballs as $gz) {
            @unlink($gz);
        }
        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('laranail.package-management.paths', [
            'packages' => $this->platform . '/packages',
            'modules' => $this->platform . '/modules',
            'plugins' => $this->platform . '/plugins',
        ]);
        $app['config']->set('laranail.package-management.cache.enabled', false);
        $app['config']->set('laranail.package-management.activation.store', 'database');
        $app['config']->set('laranail.package-management.ui.enabled', true);
        $app['config']->set('laranail.package-management.ui.prefix', 'ext');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]);
    }

    public function test_install_from_a_vcs_repo_through_the_ui(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $gz = $this->tarball(['module.json' => (string) json_encode([
            'name' => 'Gizmo', 'alias' => 'gizmo', 'providers' => [],
        ])]);
        Http::fake(['api.github.com/*' => Http::response((string) file_get_contents($gz))]);

        $this->post('/ext/install-from', ['url' => 'acme/gizmo', 'as' => 'module'])
            ->assertRedirect()
            ->assertSessionHas('status', fn (string $s): bool => str_contains($s, 'Installed [gizmo]'));

        $this->assertDirectoryExists($this->platform . '/modules/gizmo');
        $this->assertTrue(is_extension_active('gizmo'));
    }

    /** @param  array<string, string>  $files */
    private function tarball(array $files, string $wrap = 'acme-gizmo-abc123'): string
    {
        $src = sys_get_temp_dir() . '/laranail-pm-uivcs-src-' . uniqid();
        foreach ($files as $relative => $content) {
            $path = $src . '/' . $wrap . '/' . $relative;
            @mkdir(dirname($path), 0777, true);
            file_put_contents($path, $content);
        }

        $tar = sys_get_temp_dir() . '/laranail-pm-uivcs-tar-' . bin2hex(random_bytes(6)) . '.tar';
        $phar = new PharData($tar);
        $phar->buildFromDirectory($src);
        $phar->compress(Phar::GZ);
        unset($phar);

        (new Filesystem)->deleteDirectory($src);
        @unlink($tar);

        $this->tarballs[] = $tar . '.gz';

        return $tar . '.gz';
    }
}
