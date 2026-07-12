<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests\Fixtures;

/**
 * A plain, interface-free hook — exactly what laranail/package-scaffolder generates
 * (no dependency on this package). Proves the loader duck-types the hook methods.
 */
final class PlainRecordingHook
{
    /** @var list<string> */
    public static array $calls = [];

    public function activated(object $extension): void
    {
        self::$calls[] = 'activated:' . $extension->id;
    }

    public function deactivated(object $extension): void
    {
        self::$calls[] = 'deactivated:' . $extension->id;
    }

    public function installed(object $extension): void
    {
        self::$calls[] = 'installed:' . $extension->id;
    }

    public function removed(object $extension): void
    {
        self::$calls[] = 'removed:' . $extension->id;
    }
}
