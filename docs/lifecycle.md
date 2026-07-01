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
- **install** — first activation: run migrations, publish assets/translations, then activate.
- **remove** — deactivate → drop the extension's migrations → delete files → clean published assets.
- **update** — `updating` hook → swap files → run new migrations → `updated` hook.

## Hooks (optional per extension)

An extension may ship a `Plugin` / `Module` class with static hooks, called (guarded by
`class_exists`) at the matching transition:

```
activate()  activated()  deactivate()  deactivated()
remove()    removed()     updating()    updated()
```

Each transition also fires an event (`ExtensionActivated`, `ExtensionDeactivated`, …) for host apps to
react to.

## Dependency ordering

Load and activation order is a **topological sort** over each extension's `require` list, so a plugin
that requires another is always registered after it. Cycles and missing dependencies are reported at
activation time.

[← Docs index](../README.md#documentation)
