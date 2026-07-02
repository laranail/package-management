<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Extension discovery paths
    |--------------------------------------------------------------------------
    |
    | Where the loader scans for generated extensions. Each role has its own
    | container, but a single generated repo may live in any of them and carry
    | multiple manifests (composer.json / module.json / plugin.json).
    |
    */
    'paths' => [
        'packages' => base_path('platform/packages'),
        'modules' => base_path('platform/modules'),
        'plugins' => base_path('platform/plugins'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled manifest cache
    |--------------------------------------------------------------------------
    |
    | The *discovered* extension set is compiled to a PHP file for fast boots
    | (activation state is applied fresh from the store on every request, so it is
    | never baked in). Rebuild or clear with `laranail::package-management.cache`.
    |
    */
    'cache' => [
        'enabled' => env('PACKAGE_MANAGEMENT_CACHE', true),
        'path' => 'bootstrap/cache/laranail-extensions.php',
    ],

    /*
    |--------------------------------------------------------------------------
    | Activation-state store
    |--------------------------------------------------------------------------
    |
    | Which extensions are active. `file` (default) keeps a JSON file so the
    | loader has zero database requirement; `database` uses the Eloquent-backed
    | store (model + migration, Actions → Service → Repository), also exposed via
    | the `ExtensionState` facade. Run the package migration for the DB store.
    |
    */
    'activation' => [
        'store' => env('PACKAGE_MANAGEMENT_STORE', 'file'),

        // file store
        'file' => storage_path('app/laranail/extensions_statuses.json'),

        // database store — table + connection (null = the app's default connection)
        'table' => env('PACKAGE_MANAGEMENT_TABLE', 'laranail_extension_states'),
        'connection' => env('PACKAGE_MANAGEMENT_DB_CONNECTION'),

        // wrap the database state repository in a caching decorator (reads cached, writes flush)
        'cache' => env('PACKAGE_MANAGEMENT_STATE_CACHE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Manifest pipeline
    |--------------------------------------------------------------------------
    |
    | Ordered stages each discovered extension passes through (normalize / validate
    | / enrich). Class-strings only here (cache-safe); add runtime stages/closures
    | via Extensions::pipe() or container-tag `laranail.manifest.stages`.
    |
    */
    'pipeline' => [
        'stages' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Management UI
    |--------------------------------------------------------------------------
    |
    | An optional web surface to list + enable/disable/install/remove extensions.
    | Opt-in (off by default); protect it with your own auth middleware.
    |
    */
    'ui' => [
        'enabled' => env('PACKAGE_MANAGEMENT_UI', false),
        'prefix' => env('PACKAGE_MANAGEMENT_UI_PREFIX', 'laranail/extensions'),
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | VCS installer
    |--------------------------------------------------------------------------
    |
    | Install extensions straight from a VCS provider. Tokens are read here (never
    | logged) for private repos. See docs/installer.md.
    |
    */
    'installer' => [
        'default_provider' => env('PACKAGE_MANAGEMENT_VCS', 'github'),
        'tokens' => [
            'github' => env('GITHUB_TOKEN'),
            'gitlab' => env('GITLAB_TOKEN'),
            'bitbucket' => env('BITBUCKET_TOKEN'),
        ],
        'timeout' => 60,          // seconds
        'max_bytes' => 104857600, // 100 MB archive cap
        // remove() rolls back the extension's migrations only when this is true (data-safety default off)
        'rollback_migrations_on_remove' => env('PACKAGE_MANAGEMENT_ROLLBACK_ON_REMOVE', false),
    ],

];
