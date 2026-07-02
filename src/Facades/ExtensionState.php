<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Facades;

use Illuminate\Support\Facades\Facade;
use Simtabi\Laranail\Package\Management\ExtensionStateManager;

/**
 * The extension activation-state + settings API (database store). This is the raw
 * state store; for the guarded lifecycle use the `Extensions` facade instead.
 *
 * @method static array active()
 * @method static bool isActive(string $name)
 * @method static void activate(string $name)
 * @method static void deactivate(string $name)
 * @method static void forget(string $name)
 * @method static void recordInstall(string $name, ?string $version)
 * @method static array settings(string $name)
 * @method static void putSettings(string $name, array $settings)
 *
 * @see ExtensionStateManager
 */
final class ExtensionState extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ExtensionStateManager::class;
    }
}
