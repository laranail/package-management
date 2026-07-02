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
],
```

For the **database** store, just migrate — the package's migration is auto-loaded by package-tools
(`discoversMigrations()` + `runsMigrations()`):

```bash
php artisan migrate
```

The model uses the default connection and the fixed `laranail_extension_states` table. Reads degrade to
"nothing active" until the table exists, so enabling the database store on a fresh app never fatals the
boot before you migrate.

[← Docs index](../README.md#documentation)
