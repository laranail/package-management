# Usage

## CLI

```bash
php artisan laranail::package-management.list         # list discovered extensions (role, version, state)
php artisan laranail::package-management.discover      # rescan the platform paths and report the count
php artisan laranail::package-management.enable Blog   # activate an extension (+ its dependencies)
php artisan laranail::package-management.disable Blog  # deactivate (guarded by reverse-deps)
php artisan laranail::package-management.cache         # compile the discovered-extensions cache
php artisan laranail::package-management.cache --clear # delete the compiled cache
```

The compiled cache (`config('package-management.cache')`) stores the *discovered* set only; activation
state is applied fresh from the store each request, so enable/disable never needs a rebuild — only
adding or removing an extension directory does.

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
