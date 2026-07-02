<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests\Fixtures;

use Simtabi\Laranail\Package\Management\Contracts\InstallHook;
use Simtabi\Laranail\Package\Management\Contracts\LifecycleHook;
use Simtabi\Laranail\Package\Management\Extension;

/** Records lifecycle + install-hook calls for the LifecycleTest. */
final class RecordingHook implements InstallHook, LifecycleHook
{
    /** @var list<string> */
    public static array $calls = [];

    public function activated(Extension $extension): void
    {
        self::$calls[] = 'activated:' . $extension->id;
    }

    public function deactivated(Extension $extension): void
    {
        self::$calls[] = 'deactivated:' . $extension->id;
    }

    public function installed(Extension $extension): void
    {
        self::$calls[] = 'installed:' . $extension->id;
    }

    public function removed(Extension $extension): void
    {
        self::$calls[] = 'removed:' . $extension->id;
    }
}
