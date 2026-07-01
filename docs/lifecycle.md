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
  `database/migrations` via the adapter's `RunsMigrations` capability; fires `ExtensionInstalled`.
  Adapters without a migration runner (e.g. Lumen) degrade to activate-only.
- **update** — run any pending migrations for an already-installed extension; fires `ExtensionUpdated`.
- **remove** *(planned)* — migration rollback needs per-extension batch tracking, and file deletion is
  deliberately out of scope (remove the directory / `composer remove`); today, use `disable`.

## Hooks (optional per extension)

An extension may declare a lifecycle handler in its manifest (`"hook": "<FQCN>"`). The class implements
`Simtabi\Laranail\Package\Management\Contracts\LifecycleHook` and is resolved from the container (so it
gets constructor DI) at the matching transition — the place to seed data, publish assets or warm caches:

```php
use Simtabi\Laranail\Package\Management\Contracts\LifecycleHook;
use Simtabi\Laranail\Package\Management\Extension;

final class ShopHook implements LifecycleHook
{
    public function activated(Extension $extension): void { /* seed, publish, … */ }

    public function deactivated(Extension $extension): void { /* clean up */ }
}
```

`activated` / `deactivated` ship today; `installed` / `removed` / `updating` / `updated` are planned
alongside install/remove/update. A missing or non-`LifecycleHook` class is ignored (never fatal).

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
