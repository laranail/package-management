<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\Package\Management\Contracts\ActivationStore;
use Simtabi\Laranail\Package\Management\Extension;
use Simtabi\Laranail\Package\Management\ExtensionManager;
use Simtabi\Laranail\Package\Management\ExtensionRepository;
use Simtabi\Laranail\Package\Management\Manifests\ManifestReader;

class CacheTest extends TestCase
{
    private string $activationFile;

    private string $cacheFile;

    protected function setUp(): void
    {
        $this->activationFile = sys_get_temp_dir() . '/laranail-pm-act-' . getmypid() . '-' . uniqid() . '.json';
        $this->cacheFile = sys_get_temp_dir() . '/laranail-pm-cache-' . getmypid() . '-' . uniqid() . '.php';
        parent::setUp();
    }

    protected function tearDown(): void
    {
        @unlink($this->activationFile);
        @unlink($this->cacheFile);
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
        $app['config']->set('laranail.package-management.cache.enabled', true);
        $app['config']->set('laranail.package-management.cache.path', $this->cacheFile);
    }

    private function repo(): ExtensionRepository
    {
        return $this->app->make(ExtensionRepository::class);
    }

    public function test_discovery_warms_the_compiled_cache(): void
    {
        $repo = $this->repo();

        // the provider's boot() already discovered once; start from a clean slate
        $repo->clearCache();
        $this->assertFileDoesNotExist($this->cacheFile);

        $repo->all();

        $this->assertFileExists($this->cacheFile);
        $rows = include $this->cacheFile;
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
    }

    public function test_rebuild_returns_count_and_clear_removes_the_file(): void
    {
        $repo = $this->repo();

        $this->assertSame(3, $repo->rebuildCache()); // alpha + acme/beta + acme/shop
        $this->assertFileExists($this->cacheFile);

        $repo->clearCache();
        $this->assertFileDoesNotExist($this->cacheFile);
    }

    public function test_activation_state_is_not_baked_into_the_cache(): void
    {
        $this->app->make(ExtensionManager::class)->enable('alpha');
        $this->repo()->rebuildCache();

        $rows = include $this->cacheFile;
        foreach ($rows as $row) {
            $this->assertFalse($row['enabled'], 'cache must not bake activation state');
        }

        // …yet the live view still reflects the active flag (applied fresh from the store)
        $this->assertTrue(is_extension_active('alpha'));
    }

    public function test_cache_is_served_without_rescanning_the_filesystem(): void
    {
        // warm the cache from the real fixtures, then overwrite it with a synthetic entry
        $this->repo()->rebuildCache();
        file_put_contents($this->cacheFile, "<?php\n\nreturn " . var_export([[
            'id' => 'ghost/ext', 'name' => 'Ghost', 'namespace' => 'Ghost\\', 'providers' => [],
            'version' => '1.0.0', 'require' => [], 'role' => 'plugin', 'path' => '/nowhere', 'enabled' => false,
        ]], true) . ";\n");

        // a repository pointed at non-existent paths must still return the ghost — proving
        // it read the compiled cache rather than scanning the filesystem.
        $fresh = new ExtensionRepository(
            new Filesystem,
            $this->app->make(ManifestReader::class),
            $this->app->make(ActivationStore::class),
            ['package' => '/nonexistent', 'module' => '/nonexistent', 'plugin' => '/nonexistent'],
            true,
            $this->cacheFile,
        );

        $ids = array_map(static fn (Extension $e): string => $e->id, $fresh->all());
        $this->assertContains('ghost/ext', $ids);
    }
}
