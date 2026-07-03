# Host integration

How a host app and its extensions divide responsibility. The loader is a lean, framework-agnostic
**registrar**: it discovers extensions, resolves dependency order, registers their autoloading + service
providers, and drives the activation lifecycle. Everything an extension contributes to the host —
routes, policies, views, translations, assets — is owned by **the extension's own service provider**,
exactly where the framework expects it. The loader does not (and should not) own CMS concerns.

## What the extension's provider owns

A scaffolder-generated Laravel extension self-wires all of this in its own provider (built on
`laranail/package-tools`):

| Concern | Where it lives (in the extension) |
|---|---|
| **Routes** | provider `->hasRoute('web')` / `->hasRoute('api')`, backed by config-driven `routes/*.php` (prefix / middleware / enabled all read from the extension's own config) |
| **Permissions / policies** | provider `packageBooted()` → `Gate::policy(Post::class, PostPolicy::class)` / `Gate::define('blog.moderate', …)`; policy classes in `src/Policies/` |
| **Views / translations** | provider `->hasViews('modules/blog')` (namespaced) + `->hasTranslations()`; overridable via Laravel's vendor view-publishing |
| **Frontend assets** | provider `->hasAssets()` + the loader's `extension_vite($id, $entrypoints)` helper (loads the extension's published Vite build — see [installer.md](installer.md) / usage) |
| **Panel plugins** | optional `FilamentXServiceProvider` / `NovaXServiceProvider`, each `class_exists()`-guarded so they self-disable when the panel isn't installed |

The loader's job stops at `registerProviders()` — the provider does the rest on boot.

## Host aggregation seams

When the **host** needs to react to or aggregate across extensions, use these (no loader coupling):

- **Lifecycle events** — subscribe to build/refresh a cache (menu, permissions, sitemap) on state change:
  `ExtensionActivating`/`Activated`, `Deactivating`/`Deactivated`, `Installing`/`Installed`,
  `Updating`/`Updated`, `Removing`/`Removed` (each carries `->extension`). See [lifecycle.md](lifecycle.md).
- **Query API** — enumerate + inspect: `Extensions::query()->active()->get()`, `->role('plugin')`,
  `Extensions::graph()`, `Extensions::dependents($id)`. See [usage.md](usage.md).

## Admin-menu contribution

The loader carries menu entries but never renders them — the host does. Two ways, mixable:

**1. Declarative** — a `menu` array in the manifest (`module.json` / `plugin.json`), surfaced on
`Extension::$menu`:

```json
{ "menu": [ { "label": "Shop", "url": "/admin/shop", "icon": "cart", "group": "Commerce", "order": 10 } ] }
```

**2. Computed** — the extension's `hook` class implements `Contracts\ContributesNavigation` for entries
that must be built at runtime.

The host collects both by iterating the active set:

```php
use Simtabi\Laranail\Package\Management\Contracts\ContributesNavigation;
use Simtabi\Laranail\Package\Management\Facades\Extensions;

$menu = [];
foreach (Extensions::query()->active()->get() as $extension) {
    $menu = [...$menu, ...$extension->menu];                       // declarative
    $hook = $extension->hook !== null ? app($extension->hook) : null;
    if ($hook instanceof ContributesNavigation) {
        $menu = [...$menu, ...$hook->navigation($extension)];      // computed
    }
}
// $menu is now the host's to sort (by `order`/`group`) and render.
```

## Theme layer — out of scope

A theme engine (theme resolution, template overriding, active-theme switching) is a CMS subsystem and is
**deliberately not** part of the loader. The primitives an extension already has cover per-extension
theming: namespaced views (`->hasViews()`), a configurable layout (`config('modules.blog.ui.layout', …)`),
Laravel's vendor view-override publishing, and per-extension Vite assets. Build a theme layer in the host
(or a dedicated CMS package) on top of these + the aggregation seams above.

[← Docs index](../README.md#documentation)
