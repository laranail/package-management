<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management;

use Simtabi\Laranail\Package\Management\Actions\ActivateExtension;
use Simtabi\Laranail\Package\Management\Actions\DeactivateExtension;
use Simtabi\Laranail\Package\Management\Services\ExtensionStateService;

/**
 * The public API for extension activation state + settings, backing the `ExtensionState`
 * facade. Writes go through the Actions (transactional); reads through the Service.
 *
 * This is the raw state store — it applies no dependency/lifecycle guards. For the
 * guarded lifecycle (dependencies, events, hooks, migrations, assets) use the loader
 * (`Extensions::enable/disable/install`); it ultimately writes through this same path.
 */
final readonly class ExtensionStateManager
{
    public function __construct(
        private ExtensionStateService $states,
        private ActivateExtension $activateAction,
        private DeactivateExtension $deactivateAction,
    ) {}

    /** @return list<string> */
    public function active(): array
    {
        return $this->states->activeNames();
    }

    public function isActive(string $name): bool
    {
        return $this->states->isActive($name);
    }

    public function activate(string $name): void
    {
        $this->activateAction->handle($name);
    }

    public function deactivate(string $name): void
    {
        $this->deactivateAction->handle($name);
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
