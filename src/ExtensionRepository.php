<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management;

use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\Package\Management\Contracts\ActivationStore;
use Simtabi\Laranail\Package\Management\Manifests\ManifestReader;

/**
 * Discovers extensions by scanning each role's container directory and reading its
 * manifest, tagging each with its activation state. Results are memoized per request.
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
    ) {}

    /** @return list<Extension> */
    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $active = $this->store->active();
        $found = [];

        foreach ($this->paths as $role => $dir) {
            if (! $this->files->isDirectory($dir)) {
                continue;
            }

            foreach ($this->files->directories($dir) as $extensionDir) {
                $extension = $this->reader->read($extensionDir, $role);

                if ($extension instanceof Extension) {
                    $found[] = $extension->withEnabled(in_array($extension->id, $active, true));
                }
            }
        }

        return $this->cache = $found;
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

    /** Drop the memoized set (after an activation change). */
    public function forget(): void
    {
        $this->cache = null;
    }
}
