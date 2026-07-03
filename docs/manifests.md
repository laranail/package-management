# Manifests — the scaffolder ⇄ loader contract

A generated artifact is **one repo** consumable in up to three roles, each keyed by a manifest file.
This document is the **authoritative schema** for those manifests. `laranail/package-scaffolder`
*emits* them; `laranail/package-management` *reads* them. Keep both in sync with this file.

All three normalize into a single **`Extension`** value object:

| Extension field | composer.json | module.json | plugin.json |
|---|---|---|---|
| `id` | `name` (`vendor/pkg`) | `vendor/`+`alias` | `id` |
| `name` | — (derived) | `name` | `name` |
| `namespace` | `autoload.psr-4` key | derived from provider | `namespace` |
| `providers[]` | `extra.laravel.providers` | `providers` | `provider` |
| `version` | `version` | — | `version` |
| `require[]` | `require` (composer) | — | `require` (extension ids) |
| `role` | `package` | `module` | `plugin` |
| `path` | dir | dir | dir |

## `composer.json` (role: **package**)

Always present — every artifact is a Composer package. Loading-relevant fields:

```json
{
    "name": "{vendor}/{name}",
    "autoload": { "psr-4": { "{Namespace}\\": "src/" } },
    "extra": { "laravel": { "providers": ["{Namespace}\\Providers\\{Artifact}ServiceProvider"] } }
}
```

A **package** needs no runtime — Composer autoload + Laravel package auto-discovery load it. The
loader still lists it (for management/UI) but does not runtime-register it unless also a module/plugin.

## `module.json` (role: **module**)

nwidart-compatible manifest for the module runtime. Activation-gated.

```json
{
    "name": "{Artifact}",
    "alias": "{artifact}",
    "description": "",
    "keywords": [],
    "priority": 0,
    "providers": ["{Namespace}\\Providers\\{Artifact}ServiceProvider"],
    "files": []
}
```

| Field | Type | Required | Notes |
|---|---|---|---|
| `name` | string | ✓ | StudlyCase identity |
| `alias` | string | ✓ | lowercase slug |
| `providers` | string[] | ✓ | FQCN service providers |
| `priority` | int | — | load-order hint (dependencies still win) |
| `files` | string[] | — | files to `include` at boot |
| `hook` | string | — | FQCN of a lifecycle hook — duck-typed (activated/deactivated/installed/removed), so a plain class works with no dependency on the loader; or implement `LifecycleHook`/`InstallHook` for type-safety |
| `menu` | object[] | — | data-only nav entries (`label`, `url`, `icon?`, `group?`, `order?`) a host may render; the loader never renders them (see [host-integration.md](host-integration.md)) |
| `description`, `keywords` | | — | metadata |

## `plugin.json` (role: **plugin**)

Botble-informed manifest for the plugin runtime / host ecosystem. Activation-gated; carries explicit
dependencies + a minimum-runtime guard.

```json
{
    "id": "{vendor}/{name}",
    "name": "{Artifact}",
    "namespace": "{Namespace}\\",
    "provider": "{Namespace}\\Providers\\{Artifact}ServiceProvider",
    "version": "1.0.0",
    "description": "",
    "author": "Simtabi LLC",
    "url": "https://opensource.simtabi.com",
    "require": [],
    "minimum_core_version": "1.0.0",
    "type": "plugin",
    "settings": { "per_page": 15 }
}
```

| Field | Type | Required | Notes |
|---|---|---|---|
| `id` | string | ✓ | unique `vendor/name` |
| `name` | string | ✓ | display name |
| `namespace` | string | ✓ | PSR-4 root (trailing `\\`); registered on the runtime ClassLoader → `{path}/src` |
| `provider` | string | ✓ | FQCN registered with the container |
| `version` | semver | ✓ | |
| `require` | string[] \| object | — | extension `id`s that must be active first (topologically ordered). Either a list (`["acme/core"]`, presence only) or a map of id → semver constraint (`{"acme/core": "^1.2"}`), checked against the dependency's `version` on activation |
| `minimum_core_version` | `X.Y.Z` | — | minimum `package-management` version |
| `type` | string | — | `plugin` \| `nova` \| `filament` (panel plugins) |
| `hook` | string | — | FQCN of a lifecycle hook — duck-typed (activated/deactivated/installed/removed), so a plain class works with no dependency on the loader; or implement `LifecycleHook`/`InstallHook` for type-safety |
| `settings` | object | — | default settings, seeded into the extension's state on install (defaults fill gaps; user values win). DB store only. |
| `menu` | object[] | — | data-only nav entries (`label`, `url`, `icon?`, `group?`, `order?`) a host may render; the loader never renders them (see [host-integration.md](host-integration.md)) |
| `author`, `url`, `description` | | — | metadata |

## Which manifests each flavor emits

Driven by the scaffolder's `flavors` registry (`config/artifacts.php`):

| Flavor | composer.json | module.json | plugin.json |
|---|---|---|---|
| laravel | ✓ | ✓ | ✓ |
| lumen | ✓ | ✓ | ✓ |
| vanilla | ✓ | — | — (no runtime host) |

Panel code (Nova/Filament) present ⇒ `plugin.json.type` reflects it, making the same repo consumable
as a Nova/Filament plugin. Panels are Laravel-only.

## Validation

`ManifestReader` checks the required fields (module/package need `name`; plugin needs `name` +
`provider`) and **silently skips** (returns `null` for) any manifest that fails — a malformed extension
never fatals the host boot, and there is no logging/throwing. `provider`/`providers` classes are only
registered when `class_exists()` (a stale manifest or an un-dumped autoload is skipped, not fatal).

[← Docs index](../README.md#documentation)
