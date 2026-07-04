# Facade & helpers

The `Extensions` facade (`Simtabi\Laranail\Package\Management\Facades\Extensions`) is the runtime API over
the `ExtensionManager`; `ExtensionState` (`…​\Facades\ExtensionState`) is the raw activation-state +
settings API behind the database store. Both are complemented by a few global helpers.

## `Extensions` — the guarded lifecycle + queries

```php
use Simtabi\Laranail\Package\Management\Facades\Extensions;

Extensions::all();                 // list<Extension>
Extensions::modules();             // list<Extension> (role = module)
Extensions::plugins();             // list<Extension> (role = plugin)
Extensions::active();              // list<Extension> (active)
Extensions::find('vendor/blog');   // ?Extension
Extensions::enable('vendor/blog'); // dependency- + version-guarded activation
Extensions::disable('vendor/blog');
Extensions::install('vendor/blog');
Extensions::update('vendor/blog');
Extensions::remove('vendor/blog');
```

### Querying

```php
Extensions::query()->role('plugin')->active()->get();  // immutable/chainable → list<Extension>
Extensions::query()->inactive()->ids();                // list<string>
Extensions::query()->where(fn ($e) => $e->priority > 0)->count();

Extensions::graph();                    // ['vendor/blog' => ['vendor/core'], …] — require adjacency
Extensions::dependents('vendor/core');  // list<Extension> that require vendor/core (reverse deps)
```

## `ExtensionState` — raw activation state + settings (`database` store)

Use it for inspection and per-extension settings — not to bypass the lifecycle guards:

```php
use Simtabi\Laranail\Package\Management\Facades\ExtensionState;

ExtensionState::active();                          // list<string> of active ids
ExtensionState::isActive('vendor/blog');           // bool
ExtensionState::activate('vendor/blog');           // raw activate (no dependency guard)
ExtensionState::deactivate('vendor/blog');
ExtensionState::forget('vendor/blog');             // delete the state row (activation + version + settings)
ExtensionState::recordInstall('vendor/blog', '1.2.0');
ExtensionState::putSettings('vendor/blog', ['per_page' => 15]);
ExtensionState::seedSettings('vendor/blog', ['per_page' => 15]); // defaults fill gaps; user values win
ExtensionState::settings('vendor/blog');           // array
```

Both facades write through the same Actions → Service → Repository → `ExtensionState` model path;
`Extensions::enable/install` just adds the guards, events, hooks, migrations, and asset publishing on top.

## Helpers

`function_exists`-guarded so they compose with the scaffolder's own helpers:

```php
extension_path('module', 'Blog');          // …/platform/modules/Blog
extension_path('plugin', 'Shop', 'src');   // …/platform/plugins/Shop/src
extension('vendor/blog');                   // ?Extension
is_extension_active('vendor/blog');         // bool
extension_vite('vendor/blog', 'resources/js/app.js'); // per-extension Vite tags — see host-integration.md
```

---

[← Docs index](../../README.md#documentation)
