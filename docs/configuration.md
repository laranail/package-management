# Configuration

Publish the config, then edit `config/package-management.php`:

```bash
php artisan vendor:publish \
  --provider="Simtabi\Laranail\Package\Management\Providers\ManagementServiceProvider" \
  --tag=package-management-config
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

The discovered + resolved manifest is compiled to a PHP file for fast boots; it's invalidated when the
active-set count changes and can be cleared with `laranail::package-management.cache --clear`.

```php
'cache' => [
    'enabled' => env('PACKAGE_MANAGEMENT_CACHE', true),
    'path'    => 'bootstrap/cache/laranail-extensions.php',
],
```

## `activation`

Which extensions are active. `file` (default) keeps a JSON file so the loader has **no database
requirement**; `database` is a pluggable adapter for projects that prefer a settings table.

```php
'activation' => [
    'store' => env('PACKAGE_MANAGEMENT_STORE', 'file'),
    'file'  => storage_path('app/laranail/extensions_statuses.json'),
],
```

[← Docs index](../README.md#documentation)
