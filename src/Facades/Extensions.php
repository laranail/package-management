<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Facades;

use Illuminate\Support\Facades\Facade;
use Simtabi\Laranail\Package\Management\ExtensionManager;

/**
 * @method static array all()
 * @method static array active()
 * @method static array modules()
 * @method static array plugins()
 * @method static \Simtabi\Laranail\Package\Management\Extension|null find(string $id)
 * @method static void enable(string $id)
 * @method static void disable(string $id)
 *
 * @see ExtensionManager
 */
final class Extensions extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ExtensionManager::class;
    }
}
