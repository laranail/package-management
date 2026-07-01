<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Repositories;

use Illuminate\Support\Facades\Schema;
use Simtabi\Laranail\Package\Management\Contracts\ExtensionStateRepositoryInterface;
use Simtabi\Laranail\Package\Management\Models\ExtensionState;

/**
 * Eloquent-backed activation state. Reads degrade to empty/false until the state
 * table exists (a fresh app boots the provider before `migrate` creates it), so the
 * loader never fatals during bootstrap.
 */
final class EloquentExtensionStateRepository implements ExtensionStateRepositoryInterface
{
    private bool $tableReady = false;

    /** @return list<string> */
    public function activeNames(): array
    {
        if (! $this->tableReady()) {
            return [];
        }

        return array_values(array_map(strval(...), ExtensionState::query()->active()->pluck('name')->all()));
    }

    public function isActive(string $name): bool
    {
        return $this->tableReady()
            && ExtensionState::query()->where('name', $name)->where('is_active', true)->exists();
    }

    public function find(string $name): ?ExtensionState
    {
        if (! $this->tableReady()) {
            return null;
        }

        return ExtensionState::query()->where('name', $name)->first();
    }

    public function markActive(string $name): ExtensionState
    {
        $state = ExtensionState::query()->firstOrNew(['name' => $name]);
        $state->is_active = true;
        $state->activated_at = now();
        $state->installed_at ??= now();
        $state->save();

        return $state;
    }

    public function markInactive(string $name): void
    {
        ExtensionState::query()->where('name', $name)->update([
            'is_active' => false,
            'activated_at' => null,
        ]);
    }

    public function recordInstall(string $name, ?string $version): ExtensionState
    {
        $state = ExtensionState::query()->firstOrNew(['name' => $name]);
        $state->version = $version;
        $state->installed_at ??= now();
        $state->save();

        return $state;
    }

    /** @return array<string, mixed> */
    public function settings(string $name): array
    {
        $state = $this->find($name);

        if (! $state instanceof ExtensionState) {
            return [];
        }

        return $state->settings ?? [];
    }

    /** @param  array<string, mixed>  $settings */
    public function putSettings(string $name, array $settings): void
    {
        $state = ExtensionState::query()->firstOrNew(['name' => $name]);
        $state->settings = $settings;
        $state->save();
    }

    /** Whether the state table exists yet (cached once true). */
    private function tableReady(): bool
    {
        if ($this->tableReady) {
            return true;
        }

        return $this->tableReady = Schema::hasTable((new ExtensionState)->getTable());
    }
}
