# Usage

## CLI

```bash
php artisan laranail::package-management.list         # list discovered extensions (role, version, state)
php artisan laranail::package-management.discover      # rescan the platform paths and report the count
php artisan laranail::package-management.enable Blog   # activate an extension (+ its dependencies)
php artisan laranail::package-management.disable Blog  # deactivate (guarded by reverse-deps)
php artisan laranail::package-management.install Blog  # activate + run the extension's own migrations
php artisan laranail::package-management.remove Blog   # deactivate + unpublish assets + forget state (keeps DB tables)
php artisan laranail::package-management.cache         # compile the discovered-extensions cache
php artisan laranail::package-management.cache --clear # delete the compiled cache
```

The compiled cache (`config('laranail.package-management.cache')`) stores the *discovered* set only; activation
state is applied fresh from the store each request, so enable/disable never needs a rebuild — only
adding or removing an extension directory does.

Each command also has a plain-colon alias (`package-management:<verb>`) for environments that don't
accept the `::` separator.

## Programmatic API

```php
use Simtabi\Laranail\Package\Management\Facades\Extensions;

Extensions::all();                 // list<Extension>
Extensions::modules();             // list<Extension> (role = module)
Extensions::plugins();             // list<Extension> (role = plugin)
Extensions::find('vendor/blog');   // ?Extension
Extensions::active();              // list<Extension> (active)
Extensions::enable('vendor/blog');
Extensions::disable('vendor/blog');
```

### Activation state + settings (`database` store)

`Extensions::*` is the **guarded lifecycle** (dependency checks, events, hooks, migrations, asset
publishing). The **`ExtensionState` facade** is the raw activation-state + settings API backing the
`database` store — use it for inspection and per-extension settings, not to bypass the lifecycle guards:

```php
use Simtabi\Laranail\Package\Management\Facades\ExtensionState;

ExtensionState::active();                          // list<string> of active ids
ExtensionState::isActive('vendor/blog');           // bool
ExtensionState::activate('vendor/blog');           // raw activate (no dependency guard)
ExtensionState::deactivate('vendor/blog');
ExtensionState::forget('vendor/blog');             // delete the state row (activation + version + settings)
ExtensionState::recordInstall('vendor/blog', '1.2.0');
ExtensionState::putSettings('vendor/blog', ['per_page' => 15]);
ExtensionState::settings('vendor/blog');           // array
```

Both facades ultimately write through the same Actions → Service → Repository → `ExtensionState` model
path; `Extensions::enable/install` just adds the guards, events, hooks, migrations, and asset publishing
on top.

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
