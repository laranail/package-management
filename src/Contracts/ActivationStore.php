<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Contracts;

/**
 * Persists which extensions are active. Abstracted so the loader has no hard
 * database requirement — the default is file-based; a DB store is a drop-in.
 */
interface ActivationStore
{
    /** @return list<string> active extension ids */
    public function active(): array;

    public function isActive(string $id): bool;

    public function activate(string $id): void;

    public function deactivate(string $id): void;

    /** Forget an extension's stored state entirely (used on remove/uninstall). */
    public function forget(string $id): void;
}
