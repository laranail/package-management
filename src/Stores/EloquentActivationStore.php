<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Stores;

use Simtabi\Laranail\Package\Management\Contracts\ActivationStore;
use Simtabi\Laranail\Package\Management\Contracts\RecordsInstall;
use Simtabi\Laranail\Package\Management\ExtensionStateManager;

/**
 * Database activation store: bridges the loader's ActivationStore contract to the
 * Eloquent-backed state subsystem (Manager → Actions/Service → Repository → Model).
 * Also records installed versions via {@see RecordsInstall}.
 */
final readonly class EloquentActivationStore implements ActivationStore, RecordsInstall
{
    public function __construct(private ExtensionStateManager $manager) {}

    /** @return list<string> */
    public function active(): array
    {
        return $this->manager->active();
    }

    public function isActive(string $id): bool
    {
        return $this->manager->isActive($id);
    }

    public function activate(string $id): void
    {
        $this->manager->activate($id);
    }

    public function deactivate(string $id): void
    {
        $this->manager->deactivate($id);
    }

    public function recordInstall(string $id, ?string $version): void
    {
        $this->manager->recordInstall($id, $version);
    }
}
