# ADR 0001 — "Extension" is the domain abstraction (not "package")

- **Status:** Accepted (2026-07-02)
- **Deciders:** Simtabi / maintainer

## Context

The product is published as `laranail/package-management`, so an obvious question is why the domain
types are named `Extension*` (`Extension`, `ExtensionManager`, `ExtensionState`, `Extensions` facade,
`laranail::package-management.*` commands over "extensions", `laranail_extension_states` table) rather
than `Package*`.

The core model is that **one generated repository can play three roles**, each keyed by its own manifest:

| Role | Manifest | Loaded by |
|---|---|---|
| **package** | `composer.json` | Composer autoload / Laravel auto-discovery (no runtime needed) |
| **module** | `module.json` | this loader (activation-gated) |
| **plugin** | `plugin.json` | this loader (activation-gated) |

`Extension->role` is exactly one of `package` \| `module` \| `plugin`. We therefore need a **role-neutral
umbrella term** for "a thing this package discovers and manages, regardless of which of the three roles it
is playing." Botble calls its equivalent a "plugin"; nwidart calls it a "module" — both bind the umbrella
to one specific role, which is the ambiguity we want to avoid.

## Decision

**Keep "extension" as the domain abstraction.** An *extension* is the umbrella; *package / module /
plugin* are its three roles.

- The **product** stays `laranail/package-management` because it *manages extensions* — "package
  management" is the activity, not the domain type. (Its upstream `laranail/package-tools` similarly uses
  "Package" structurally.)
- **"package" is reserved for the role**, so reusing it as the umbrella type would collide with itself
  (`Extension` whose `role === 'package'` vs a top-level `Package`), which is precisely what the
  abstraction exists to prevent.
- No mass rename. The rename map is **empty by design**; the naming is already consistent across
  namespaces, the `ExtensionState` model + `laranail_extension_states` table, factory/seeder, config
  (`laranail.package-management.*`), commands, facades, events, UI routes (`laranail.extensions.*`), the
  `package-management` view namespace, and helpers (`extension_path()`, `extension()`,
  `is_extension_active()`).

### Related conventions (kept intentional)
- `Models\ExtensionState` and `Facades\ExtensionState` share a short name but are distinct FQCNs (record
  vs API); import the one you need. Acceptable — the model is the row, the facade is the state service.
- The `ExtensionState` model + `ExtensionStateFactory` are not `final` (Eloquent/factory convention);
  everything else in `src/` is `final`.

## Consequences

- The docs and code speak of "extensions" as the first-class noun; a "package" is one kind of extension.
- If a future maintainer wants "package" as the umbrella, that is a breaking rename of ~25 identifiers,
  the table, config keys, commands and events — and reintroduces the role/umbrella name collision. This
  ADR is the record of why we did not.

[← Docs index](../../README.md#documentation)
