<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Installer;

use Throwable;

/**
 * A LIFO stack of undo callables. Each install step registers its undo; on failure the
 * stack unwinds in reverse (best-effort), leaving no orphaned files or tables.
 */
final class RollbackStack
{
    /** @var list<callable():void> */
    private array $undos = [];

    public function push(callable $undo): void
    {
        $this->undos[] = $undo;
    }

    public function unwind(): void
    {
        foreach (array_reverse($this->undos) as $undo) {
            try {
                $undo();
            } catch (Throwable) {
                // best-effort cleanup — keep unwinding the rest of the stack
            }
        }

        $this->undos = [];
    }

    public function commit(): void
    {
        $this->undos = [];
    }
}
