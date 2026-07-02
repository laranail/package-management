# Configuration

Built on `laranail/package-tools`: the config file `config/package-management.php` is merged under the
vendor-namespaced key **`config('laranail.package-management.*')`**. Publish it to customise:

```bash
php artisan vendor:publish --tag=laranail::package-management-config
```

## `paths`

Where the loader scans for extensions — one container per role. A generated repo may live in any of
them and carry multiple manifests.

```php
'paths' => [
    'packages' => base_path('platform/packages'),
    'modules'  => base_path('platform/modules'),
    'plugins'  => base_path('platform/plugins'),
],
```

## `cache`

The *discovered* extension set is compiled to a PHP file for fast boots (activation state is applied
fresh from the store each request, never baked in). Build/clear it with
`laranail::package-management.cache [--clear]`.

```php
'cache' => [
    'enabled' => env('PACKAGE_MANAGEMENT_CACHE', true),
    'path'    => 'bootstrap/cache/laranail-extensions.php',
],
```

## `activation`

Which extensions are active. `file` (default) keeps a JSON file so the loader has **no database
requirement**; `database` uses the **Eloquent-backed store** — the `ExtensionState` model (rich state
table) behind a layered subsystem (Facade → Manager → Actions/Service → Repository), also exposed to
host apps via the [`ExtensionState` facade](usage.md).

```php
'activation' => [
    'store' => env('PACKAGE_MANAGEMENT_STORE', 'file'),

    // file store
    'file' => storage_path('app/laranail/extensions_statuses.json'),

    // database store — table + connection (null = the app's default connection)
    'table' => env('PACKAGE_MANAGEMENT_TABLE', 'laranail_extension_states'),
    'connection' => env('PACKAGE_MANAGEMENT_DB_CONNECTION'),

    // wrap the DB state repository in a caching decorator (see docs/extensibility.md)
    'cache' => env('PACKAGE_MANAGEMENT_STATE_CACHE', false),
],
```

For the **database** store, just migrate — the package's migration is auto-loaded by package-tools
(`discoversMigrations()` + `runsMigrations()`):

```bash
php artisan migrate
```

The table name and connection are config-driven (`activation.table` / `activation.connection`) — both the
model and the migration honour them, so the package drops into any app's schema. Reads degrade to
"nothing active" until the table exists, so enabling the database store on a fresh app never fatals the
boot before you migrate.

## `ui`

An **opt-in** web surface to list + enable/disable/install/remove extensions. Off by default; protect
it with your own auth middleware.

```php
'ui' => [
    'enabled'    => env('PACKAGE_MANAGEMENT_UI', false),
    'prefix'     => env('PACKAGE_MANAGEMENT_UI_PREFIX', 'laranail/extensions'),
    'middleware' => ['web'],
],
```

When enabled it registers `GET {prefix}` (the list) + `POST {prefix}/{enable,disable,install,remove}`
(named `laranail.extensions.*`). Publish/override the Blade view with the
`laranail::package-management-config` publish or the `package-management` view namespace.

[← Docs index](../README.md#documentation)
