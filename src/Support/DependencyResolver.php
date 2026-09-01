<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Support;

use RuntimeException;
use Simtabi\Laranail\Package\Management\Extension;

/**
 * Topologically sorts extensions by their `require` list so a dependency is always
 * registered before its dependents. Detects cycles; missing dependencies are
 * skipped here (enforced loudly at activate time in ExtensionManager).
 */
final class DependencyResolver
{
    /** @var array<string, Extension> */
    private array $byId = [];

    /** @var array<string, true> */
    private array $visiting = [];

    /** @var array<string, true> */
    private array $visited = [];

    /** @var list<Extension> */
    private array $sorted = [];

    /**
     * @param  list<Extension>  $extensions
     * @return list<Extension>
     */
    public function sort(array $extensions): array
    {
        $this->byId = [];
        $this->visiting = [];
        $this->visited = [];
        $this->sorted = [];

        foreach ($extensions as $extension) {
            $this->byId[$extension->id] = $extension;
        }

        foreach ($extensions as $extension) {
            $this->visit($extension);
        }

        return $this->sorted;
    }

    private function visit(Extension $extension): void
    {
        if (isset($this->visited[$extension->id])) {
            return;
        }

        if (isset($this->visiting[$extension->id])) {
            throw new RuntimeException("Dependency cycle detected at extension [{$extension->id}].");
        }

        $this->visiting[$extension->id] = true;

        foreach ($extension->require as $dependency) {
            if (isset($this->byId[$dependency])) {
                $this->visit($this->byId[$dependency]);
            }
        }

        unset($this->visiting[$extension->id]);
        $this->visited[$extension->id] = true;
        $this->sorted[] = $extension;
    }
}
