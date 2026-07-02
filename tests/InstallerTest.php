<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Phar;
use PharData;
use Simtabi\Laranail\Package\Management\Installer\Drivers\GithubSourceDriver;
use Simtabi\Laranail\Package\Management\Installer\ExtensionInstaller;
use Simtabi\Laranail\Package\Management\Installer\RepositoryRef;
use Throwable;

class InstallerTest extends TestCase
{
    use RefreshDatabase;

    private string $platform;

    /** @var list<string> */
    private array $tarballs = [];

    protected function setUp(): void
    {
        $this->platform = sys_get_temp_dir() . '/laranail-pm-platform-' . getmypid() . '-' . uniqid();
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
        $app['config']->set('laranail.package-management.paths', [
            'packages' => $this->platform . '/packages',
            'modules' => $this->platform . '/modules',
            'plugins' => $this->platform . '/plugins',
        ]);
        $app['config']->set('laranail.package-management.cache.enabled', false);
        $app['config']->set('laranail.package-management.activation.store', 'database');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]);
    }

    public function test_installs_a_module_from_a_vcs_tarball(): void
    {
        $gz = $this->tarball([
            'module.json' => (string) json_encode(['name' => 'Widget', 'alias' => 'widget', 'providers' => []]),
            'database/migrations/2026_01_01_000000_create_widget_items_table.php' => $this->migration('widget_items'),
        ]);
        Http::fake(['api.github.com/*' => Http::response((string) file_get_contents($gz))]);

        $extension = $this->app->make(ExtensionInstaller::class)->install(RepositoryRef::parse('acme/widget'));

        $this->assertSame('widget', $extension->id);
        $this->assertDirectoryExists($this->platform . '/modules/widget');       // lowercase target
        $this->assertTrue(is_extension_active('widget'));                       // activated
        $this->assertTrue(Schema::hasTable('widget_items'));                    // migrated
    }

    public function test_rollback_leaves_no_files_or_tables_on_failure(): void
    {
        $gz = $this->tarball([
            'module.json' => (string) json_encode(['name' => 'Broken', 'alias' => 'broken', 'providers' => []]),
            'database/migrations/2026_01_01_000000_boom.php' => $this->throwingMigration(),
        ]);
        Http::fake(['api.github.com/*' => Http::response((string) file_get_contents($gz))]);

        try {
            $this->app->make(ExtensionInstaller::class)->install(RepositoryRef::parse('acme/broken'));
            $this->fail('Expected the failing migration to abort the install.');
        } catch (Throwable) {
            // expected
        }

        $this->assertDirectoryDoesNotExist($this->platform . '/modules/broken'); // target removed
        $this->assertFalse(Schema::hasTable('broken_items'));                  // no orphan table
        $this->assertFalse(is_extension_active('broken'));                    // state unwound
    }

    public function test_live_github_download_smoke(): void
    {
        if (! getenv('PACKAGE_MANAGEMENT_LIVE_INSTALL_TEST')) {
            $this->markTestSkipped('Set PACKAGE_MANAGEMENT_LIVE_INSTALL_TEST=1 to run the live VCS download smoke.');
        }

        $temp = sys_get_temp_dir() . '/laranail-pm-live-' . uniqid();
        (new Filesystem)->ensureDirectoryExists($temp);

        // hits the real GitHub tarball API for the canonical public test repo
        $token = getenv('GITHUB_TOKEN');
        $path = (new GithubSourceDriver(60, $token === false ? null : $token))
            ->download(RepositoryRef::parse('octocat/Hello-World'), $temp);

        $this->assertFileExists($path);
        $this->assertGreaterThan(0, (int) filesize($path));

        (new Filesystem)->deleteDirectory($temp);
    }

    /** @param  array<string, string>  $files */
    private function tarball(array $files, string $wrap = 'acme-widget-abc123'): string
    {
        $src = sys_get_temp_dir() . '/laranail-pm-tarsrc-' . uniqid();
        foreach ($files as $relative => $content) {
            $path = $src . '/' . $wrap . '/' . $relative;
            @mkdir(dirname($path), 0777, true);
            file_put_contents($path, $content);
        }

        $tar = sys_get_temp_dir() . '/laranail-pm-tar-' . bin2hex(random_bytes(6)) . '.tar';
        $phar = new PharData($tar);
        $phar->buildFromDirectory($src);
        $phar->compress(Phar::GZ);
        unset($phar);

        (new Filesystem)->deleteDirectory($src);
        @unlink($tar);

        $this->tarballs[] = $tar . '.gz';

        return $tar . '.gz';
    }

    private function migration(string $table): string
    {
        return '<?php return new class extends \Illuminate\Database\Migrations\Migration {'
            . ' public function up(): void { \Illuminate\Support\Facades\Schema::create("' . $table . '", function ($t) { $t->id(); }); }'
            . ' public function down(): void { \Illuminate\Support\Facades\Schema::dropIfExists("' . $table . '"); } };';
    }

    private function throwingMigration(): string
    {
        return '<?php return new class extends \Illuminate\Database\Migrations\Migration {'
            . ' public function up(): void { throw new \RuntimeException("boom"); }'
            . ' public function down(): void {} };';
    }
}
