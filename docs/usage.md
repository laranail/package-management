# Usage

## CLI

```bash
php artisan laranail::package-management.list        # list discovered extensions (role, version, state)
php artisan laranail::package-management.discover     # rebuild the compiled manifest cache
php artisan laranail::package-management.enable Blog  # activate an extension (+ its dependencies)
php artisan laranail::package-management.disable Blog # deactivate (guarded by reverse-deps)
php artisan laranail::package-management.cache --clear
```

`module:*` / `plugin:*` aliases are provided for familiarity.

## Programmatic API

```php
use Simtabi\Laranail\Package\Management\Facades\Extensions;

Extensions::all();                 // Collection<Extension>
Extensions::modules();             // by role
Extensions::plugins();
Extensions::find('vendor/blog');   // ?Extension
Extensions::active();              // active set
Extensions::enable('vendor/blog');
Extensions::disable('vendor/blog');
```

Helpers (function_exists-guarded so they compose with the scaffolder's):

```php
extension_path('module', 'Blog');          // …/platform/modules/Blog
extension_path('plugin', 'Shop', 'src');   // …/platform/plugins/Shop/src
extension('vendor/blog');                   // ?Extension
is_extension_active('vendor/blog');         // bool
```

## How loading works

On boot the provider discovers extensions, resolves dependency order, registers PSR-4 + providers for
the **active** ones, then wires their backend/frontend. A **package** (composer.json only) is loaded by
Composer/Laravel directly and simply listed here; **modules/plugins** are runtime-loaded and gated by
activation state. See [lifecycle.md](lifecycle.md).

[← Docs index](../README.md#documentation)
