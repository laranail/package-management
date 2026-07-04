# Getting started

A guided walkthrough: drop an extension into a Laravel app, discover it, activate it, and verify it loaded.
For the 30-second version see the README [Quick start](../README.md#quick-start).

## 1. Install the loader

```bash
composer require laranail/package-management
php artisan vendor:publish --tag=laranail::package-management-config   # optional
```

## 2. Drop in an extension

Generate one with [`laranail/package-scaffolder`](https://github.com/laranail/package-scaffolder) (or drop
an existing artifact) under the `platform/` tree the loader scans:

```
platform/modules/Blog/
├── module.json          # { "name": "Blog", "alias": "blog", "providers": [...] }
├── composer.json
└── src/ …
```

The `module.json` is the loader's contract — see [Manifests](manifests.md).

## 3. Discover it

```bash
php artisan laranail::package-management.discover   # rescan + (re)build the manifest cache
php artisan laranail::package-management.list       # blog · module · 1.0.0 · inactive
```

Discovery is drop-in: no `composer dump-autoload` is needed for the loader to see the extension.

## 4. Activate + install it

```bash
php artisan laranail::package-management.enable blog     # activate (dependency- + version-guarded)
php artisan laranail::package-management.install blog    # activate + migrate + publish assets + seed settings
```

Or from code:

```php
use Simtabi\Laranail\Package\Management\Facades\Extensions;

Extensions::install('blog');
```

## 5. Verify

```php
is_extension_active('blog');                 // true
Extensions::query()->active()->ids();        // ['blog']
extension('blog')->version;                  // '1.0.0'
```

The extension's service provider is now registered on every boot (in dependency order) — its routes,
views, policies and assets are wired by the provider itself (see [Host integration](tools/host-integration.md)).

## Next steps

- [Commands](tools/commands.md) — the full CLI (`update`, `remove`, `cache`, `install-from`, …).
- [Facade & helpers](tools/facade.md) — the programmatic API, querying, and the state facade.
- [Configuration](configuration.md) — discovery paths, the compiled cache, and the database store.
- [Lifecycle](lifecycle.md) — states, transitions, hooks, and events.
- [Installer](tools/installer.md) — install straight from GitHub / GitLab / Bitbucket.

---

[← Docs index](../README.md#documentation)
