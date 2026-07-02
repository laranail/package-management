<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Services;

use Simtabi\Laranail\Package\Management\Contracts\ExtensionStateRepositoryInterface;

/**
 * Read + orchestration layer over the state repository. Actions call it for writes;
 * the manager calls it directly for reads.
 */
final readonly class ExtensionStateService
{
    public function __construct(private ExtensionStateRepositoryInterface $states) {}

    /** @return list<string> */
    public function activeNames(): array
    {
        return $this->states->activeNames();
    }

    public function isActive(string $name): bool
    {
        return $this->states->isActive($name);
    }

    public function activate(string $name): void
    {
        $this->states->markActive($name);
    }

    public function deactivate(string $name): void
    {
        $this->states->markInactive($name);
    }

    public function forget(string $name): void
    {
        $this->states->forget($name);
    }

    public function recordInstall(string $name, ?string $version): void
    {
        $this->states->recordInstall($name, $version);
    }

    /** @return array<string, mixed> */
    public function settings(string $name): array
    {
        return $this->states->settings($name);
    }

    /** @param  array<string, mixed>  $settings */
    public function putSettings(string $name, array $settings): void
    {
        $this->states->putSettings($name, $settings);
    }
}
