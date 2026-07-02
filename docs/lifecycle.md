# Lifecycle

## States

An extension is **discovered** (found + valid manifest) and either **active** or **inactive**
(activation state, per the `ActivationStore`). Only active modules/plugins are runtime-registered.
Packages (composer.json only) are always Composer-loaded and are not activation-gated.

## Transitions

```
discovered ──enable──▶ active ──disable──▶ inactive ──remove──▶ (gone)
      ▲                                          │
      └──────────────── install ◀───────────────┘
```

- **enable / activate** — validate manifest → check `minimum_core_version` → ensure `require`
  dependencies are active (else fail) → register provider → run hooks → persist to the store.
- **disable / deactivate** — reverse-dependency guard (refuse if an active extension requires it) →
  run hooks → remove from the store.
- **install** — activate (dependency-checked), then run the extension's own
  `database/migrations` (adapter `RunsMigrations`) and publish its `public/` assets to
  `public/vendor/{slug}` (adapter `PublishesAssets`); fires `ExtensionInstalled`. Adapters lacking a
  capability (e.g. Lumen) degrade to activate-only.
- **update** — run any pending migrations for an already-installed extension; fires `ExtensionUpdated`.
- **remove** (uninstall) — deactivate, unpublish the extension's assets, and forget its management-state
  row (activation flag, version, settings); fires `ExtensionRemoved`. The extension's own database
  **tables are preserved** (removing a plugin must not destroy user data — drop them deliberately with a
  migration if desired). Migration rollback + directory deletion remain out of scope.

## Hooks (optional per extension)

An extension may declare a lifecycle handler in its manifest (`"hook": "<FQCN>"`). It is resolved from
the container (so it gets constructor DI) and **duck-typed** — the loader calls whichever of
`activated` / `deactivated` / `installed` / `removed` exist — so a scaffolder-generated hook needs
**no dependency on this package** (a plain class with those methods, taking the Extension object):

```php
final class ShopHook  // no import, no interface — the scaffolder default
{
    public function activated(object $extension): void { /* warm caches, … */ }
    public function installed(object $extension): void { /* seed reference data, … */ }
    public function removed(object $extension): void { /* clean up */ }
}
```

If you already depend on this package, implementing the interfaces is an optional, type-safe way to
declare the same methods (`Extension` typed, IDE-checked):

```php
use Simtabi\Laranail\Package\Management\Contracts\LifecycleHook;
use Simtabi\Laranail\Package\Management\Extension;

final class ShopHook implements LifecycleHook
{
    public function activated(Extension $extension): void { /* seed, publish, … */ }

    public function deactivated(Extension $extension): void { /* clean up */ }
}
```

`Contracts\InstallHook` (`installed` / `removed`) complements `Contracts\LifecycleHook` (`activated` /
`deactivated`); implement either or both. `activated`/`deactivated`/`installed`/`removed` ship today;
`updating`/`updated` are planned. A missing hook class — or one missing a given method — is simply
skipped (never fatal).

## Events

Each transition also fires an event for host apps to react to — `ExtensionActivated` /
`ExtensionDeactivated` (each carrying the `Extension`). Listen with the normal event dispatcher:

```php
use Simtabi\Laranail\Package\Management\Events\ExtensionActivated;

Event::listen(ExtensionActivated::class, fn ($e) => logger()->info("enabled {$e->extension->id}"));
```

## Dependency ordering

Load and activation order is a **topological sort** over each extension's `require` list, so a plugin
that requires another is always registered after it. Cycles and missing dependencies are reported at
activation time.

[← Docs index](../README.md#documentation)
