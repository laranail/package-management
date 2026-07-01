<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Stores;

use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Schema;
use Simtabi\Laranail\Package\Management\Contracts\ActivationStore;

/**
 * Database-backed activation store: one row per extension in a small state table.
 * The pluggable alternative to {@see FileActivationStore} for projects that prefer
 * activation to live in the database (config `activation.store = 'database'`).
 *
 * Reads degrade gracefully to "nothing active" when the state table doesn't exist
 * yet (a fresh install boots the provider before `migrate` creates the table), so
 * the loader never fatals during bootstrap.
 */
final class DatabaseActivationStore implements ActivationStore
{
    private bool $tableReady = false;

    public function __construct(
        private readonly ConnectionResolverInterface $db,
        private readonly string $table = 'laranail_extension_states',
        private readonly ?string $connection = null,
    ) {}

    /** @return list<string> */
    public function active(): array
    {
        if (! $this->tableReady()) {
            return [];
        }

        return array_values(array_map(
            static fn (mixed $name): string => (string) $name,
            $this->query()->where('is_active', true)->pluck('name')->all(),
        ));
    }

    public function isActive(string $id): bool
    {
        return $this->tableReady()
            && $this->query()->where('name', $id)->where('is_active', true)->exists();
    }

    public function activate(string $id): void
    {
        $this->query()->updateOrInsert(['name' => $id], ['is_active' => true]);
    }

    public function deactivate(string $id): void
    {
        $this->query()->updateOrInsert(['name' => $id], ['is_active' => false]);
    }

    private function query(): Builder
    {
        return $this->db->connection($this->connection)->table($this->table);
    }

    /** Whether the state table exists yet (cached once true). */
    private function tableReady(): bool
    {
        if ($this->tableReady) {
            return true;
        }

        return $this->tableReady = Schema::connection($this->connection)->hasTable($this->table);
    }
}
