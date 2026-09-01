<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Contracts;

use Simtabi\Laranail\Package\Management\Models\ExtensionState;

/**
 * Persistence contract for extension activation state. Bound to an Eloquent
 * implementation; abstracted so the store never touches the model directly.
 */
interface ExtensionStateRepositoryInterface
{
    /** @return list<string> */
    public function activeNames(): array;

    public function isActive(string $name): bool;

    public function find(string $name): ?ExtensionState;

    public function markActive(string $name): ExtensionState;

    public function markInactive(string $name): void;

    /** Delete the state row entirely. */
    public function forget(string $name): void;

    public function recordInstall(string $name, ?string $version): ExtensionState;

    /** @return array<string, mixed> */
    public function settings(string $name): array;

    /** @param  array<string, mixed>  $settings */
    public function putSettings(string $name, array $settings): void;

    /**
     * Seed default settings — defaults only fill gaps, existing values win.
     *
     * @param  array<string, mixed>  $defaults
     */
    public function seedSettings(string $name, array $defaults): void;
}
