<?php

declare(strict_types=1);

use Simtabi\Laranail\Package\Management\Extension;
use Simtabi\Laranail\Package\Management\ExtensionManager;

/*
|--------------------------------------------------------------------------
| package-management helpers
|--------------------------------------------------------------------------
|
| Global helpers for locating and querying extensions at runtime. All are
| function_exists-guarded so they compose safely with laranail/package-scaffolder
| (which ships its own module()/module_path() helpers). The runtime accessors
| (extension()/is_extension_active()) are wired to the loader in the core phase.
|
*/

if (! function_exists('extension_path')) {
    /**
     * Absolute path to an extension of the given role (package|module|plugin).
     */
    function extension_path(string $role, string $name, string $path = ''): string
    {
        $base = (string) config("package-management.paths.{$role}s", base_path("platform/{$role}s"));
        $full = $base . DIRECTORY_SEPARATOR . $name;

        return $path === '' ? $full : $full . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }
}

if (! function_exists('extension')) {
    /**
     * Resolve a discovered extension by id (composer name / module alias / plugin id).
     */
    function extension(string $id): ?Extension
    {
        return app(ExtensionManager::class)->find($id);
    }
}

if (! function_exists('is_extension_active')) {
    /**
     * Whether an extension is discovered and active.
     */
    function is_extension_active(string $id): bool
    {
        $extension = app(ExtensionManager::class)->find($id);

        return $extension !== null && $extension->enabled;
    }
}
