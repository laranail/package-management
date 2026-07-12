<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Facades;

use Illuminate\Support\Facades\Facade;
use Simtabi\Laranail\Package\Management\Extension;
use Simtabi\Laranail\Package\Management\ExtensionManager;
use Simtabi\Laranail\Package\Management\Support\ExtensionQuery;

/**
 * @method static array all()
 * @method static array active()
 * @method static array modules()
 * @method static array plugins()
 * @method static Extension|null find(string $id)
 * @method static ExtensionQuery query()
 * @method static array<string, list<string>> graph()
 * @method static list<Extension> dependents(string $id)
 * @method static void enable(string $id)
 * @method static void disable(string $id)
 * @method static void install(string $id)
 * @method static void update(string $id)
 * @method static void remove(string $id)
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
