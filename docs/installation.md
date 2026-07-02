# Installation

```bash
composer require laranail/package-management
```

The `ManagementServiceProvider` (built on `laranail/package-tools`) is auto-discovered. Publish the
config to customise paths/cache/store:

```bash
php artisan vendor:publish --tag=laranail::package-management-config
```

Config resolves under the vendor-namespaced key `config('laranail.package-management.*')`.

## Requirements

- PHP `^8.4.1 || ^8.5`
- Laravel `^13` (the shipping adapter; other frameworks via a `LoaderAdapter` — see
  [extending.md](extending.md)).

## Layout

Place generated extensions (from `laranail/package-scaffolder`) under:

```
platform/
├── packages/{Name}/   # composer.json — standalone Composer packages
├── modules/{Name}/    # module.json  — activation-gated modules
└── plugins/{Name}/    # plugin.json  — activation-gated plugins
```

Paths are configurable in `config/package-management.php`. Run `php artisan
laranail::package-management.discover` to (re)build the compiled manifest after adding extensions.

[← Docs index](../README.md#documentation)
