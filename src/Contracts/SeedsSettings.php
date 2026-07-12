<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Contracts;

/**
 * Optional activation-store capability: seed an extension's manifest default settings
 * on install (defaults only fill gaps — existing/user values win, so a reinstall never
 * clobbers customisations). Stores without settings (e.g. the file store) don't
 * implement it, and the manager skips the step.
 */
interface SeedsSettings
{
    /** @param  array<string, mixed>  $defaults */
    public function seedSettings(string $id, array $defaults): void;
}
