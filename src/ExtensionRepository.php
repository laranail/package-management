<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management;

use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\Package\Management\Contracts\ActivationStore;
use Simtabi\Laranail\Package\Management\Manifests\ManifestReader;
use Simtabi\Laranail\Package\Management\Processing\ManifestPipeline;

/**
 * Discovers extensions by scanning each role's container directory and reading its
 * manifest. The discovered set is memoized per request and, when caching is enabled,
 * compiled to a PHP file so boots skip the filesystem scan (Botble-style). Activation
 * state is applied fresh from the ActivationStore on every request — it is never
 * baked into the cache, so enabling/disabling never requires a rebuild; only adding or
 * removing an extension directory does (`…​.cache` / `…​.cache --clear`).
 */
final class ExtensionRepository
{
    /** @var list<Extension>|null */
    private ?array $cache = null;

    /**
     * @param  array<string, string>  $paths  role => container directory (package|module|plugin)
     */
    public function __construct(
        private readonly Filesystem $files,
        private readonly ManifestReader $reader,
        private readonly ActivationStore $store,
        private readonly array $paths,
        private readonly bool $cacheEnabled = false,
        private readonly string $cachePath = '',
        private readonly ?ManifestPipeline $pipeline = null,
    ) {}

    /** @return list<Extension> */
    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $active = $this->store->active();

        return $this->cache = array_map(
            static fn (Extension $e): Extension => $e->withEnabled(in_array($e->id, $active, true)),
            $this->discovered(),
        );
    }

    /** @return list<Extension> */
    public function byRole(string $role): array
    {
        return array_values(array_filter($this->all(), static fn (Extension $e): bool => $e->role === $role));
    }

    public function find(string $id): ?Extension
    {
        foreach ($this->all() as $extension) {
            if ($extension->id === $id) {
                return $extension;
            }
        }

        return null;
    }

    /** @return list<Extension> */
    public function active(): array
    {
        return array_values(array_filter($this->all(), static fn (Extension $e): bool => $e->enabled));
    }

    /** Force a fresh scan, (re)write the compiled cache, and return the count. */
    public function rebuildCache(): int
    {
        $scanned = $this->scan();

        if ($this->cachePath !== '') {
            $this->writeCache($scanned);
        }

        $this->cache = null;

        return count($scanned);
    }

    /** Delete the compiled cache file (if any). */
    public function clearCache(): void
    {
        if ($this->cachePath !== '' && $this->files->isFile($this->cachePath)) {
            $this->files->delete($this->cachePath);
        }

        $this->cache = null;
    }

    /** Drop the per-request memoization (after an activation change). */
    public function forget(): void
    {
        $this->cache = null;
    }

    /**
     * The discovered set (without activation state) — from the compiled cache when
     * available, otherwise a fresh scan that warms the cache.
     *
     * @return list<Extension>
     */
    private function discovered(): array
    {
        if ($this->cacheEnabled && $this->cachePath !== '' && $this->files->isFile($this->cachePath)) {
            $data = @include $this->cachePath;

            if (is_array($data)) {
                return array_values(array_map(
                    Extension::fromArray(...),
                    $data,
                ));
            }
        }

        $scanned = $this->scan();

        if ($this->cacheEnabled && $this->cachePath !== '') {
            $this->writeCache($scanned);
        }

        return $scanned;
    }

    /** @return list<Extension> */
    private function scan(): array
    {
        $found = [];

        foreach ($this->paths as $role => $dir) {
            if (! $this->files->isDirectory($dir)) {
                continue;
            }

            foreach ($this->files->directories($dir) as $extensionDir) {
                $extension = $this->reader->read($extensionDir, $role);

                if ($extension instanceof Extension) {
                    $found[] = $this->pipeline?->process($extension) ?? $extension;
                }
            }
        }

        return $found;
    }

    /** @param  list<Extension>  $extensions */
    private function writeCache(array $extensions): void
    {
        $rows = array_map(static fn (Extension $e): array => $e->toArray(), $extensions);

        $this->files->ensureDirectoryExists(dirname($this->cachePath));
        $this->files->put($this->cachePath, "<?php\n\nreturn " . var_export($rows, true) . ";\n");
    }
}
