# Architecture

`laranail/package-management` is the **runtime loader/manager** for the laranail scaffolding
ecosystem. It is the counterpart to `laranail/package-scaffolder`:

- **`package-scaffolder`** — *author-time*. Generates an artifact (via `make:artifact`) that carries
  manifests (`composer.json`, `module.json`, `plugin.json`).
- **`package-management`** — *run-time*. Discovers those artifacts in a host project, resolves their
  dependencies, registers their autoloading + service providers, activates them, and wires their
  backend and frontend into the host — **drop-in, no per-extension `composer dump` required.**

The two packages share one contract: the **manifest schemas** ([manifests.md](manifests.md)).

The first-class domain type is the **extension** — the role-neutral umbrella over package/module/plugin;
see [ADR 0001](adr/0001-extension-as-the-abstraction.md) for why the types are `Extension*` and not
`Package*`.

## Design goals

1. **Drop-in.** `composer require laranail/package-management`, drop extensions under `platform/`, done.
2. **Framework-adaptable.** A Laravel adapter ships first; Lumen/Symfony adapters plug in behind an
   interface (mirrors the scaffolder's framework *flavors*).
3. **No hard database requirement.** Activation state is file-based by default; a database store is an
   optional adapter (Botble hardcodes a settings table — we abstract it).
4. **Deterministic load order.** Extensions are topologically sorted by their `require` dependencies.
5. **Fast boots.** The resolved manifest is compiled to a PHP file with a cheap validity check.
6. **Separation of concerns.** Loading pulls none of the generator/blueprint machinery.

## The pipeline

```
  host project  (Laravel now; Lumen / Symfony via a LoaderAdapter)
        │  composer require laranail/package-management        ← drop-in
        ▼
  ManagementServiceProvider::boot()
        │
        ▼
  ExtensionRepository::all()  — scans platform/{packages,modules,plugins}/*
        │   read composer.json / module.json / plugin.json  →  Extension[]  (value objects)
        │   (compiled cache: bootstrap/cache/laranail-extensions.php; rebuild via the .cache / .discover commands)
        ▼
  DependencyResolver::sort($extensions)           ActivationStore::active()
        │   topological order over `require`            │   file (default) | database
        ▼                                                ▼
  LoaderAdapter (Laravel):
        ClassLoader::setPsr4($namespace, $path/src)->register()      ← runtime PSR-4
        foreach provider:  class_exists() ? $app->register($provider)  ← skip missing, never fatal
        │
        ▼
  Backend glue:  routes (web/api/admin) · migrations · commands · policies/permissions · admin menu
  Frontend glue: views · Blade components · Vite assets · themes
        │
        ▼
  Lifecycle:  install · activate · update · deactivate · remove — each fires a pre/post event pair
              (+ duck-typed hooks)   guarded by dependency + semver + `minimum_core_version` checks
              (full event matrix: extensibility.md §5)
```

## Components

| Component | Responsibility |
|---|---|
| **`Extension` (VO)** | Immutable value object: `id, name, namespace, providers[], version, require[], role, path, enabled` + manifest metadata `hook, defaultSettings, priority, type, minimumCoreVersion, requireVersions, menu`. Built from a manifest. |
| **`ManifestReader`** | Parse + validate `composer.json` / `module.json` / `plugin.json` against the schema; merge into one `Extension`. |
| **`ExtensionRepository`** | Discover extensions under the configured `platform/*` paths; produce/read the **compiled manifest cache**; query by role/name/enabled. |
| **`DependencyResolver`** | Topologically sort by `require`; detect cycles + missing deps; enforce `minimum_core_version`. |
| **`ActivationStore` (interface)** | Read/write the active set. `FileActivationStore` (default JSON) + `EloquentActivationStore` — the latter bridges to the Eloquent **state subsystem**: Facade → `ExtensionStateManager` → Actions (writes) / `ExtensionStateService` (reads) → `ExtensionStateRepositoryInterface` → `ExtensionState` model. |
| **`LoaderAdapter` (interface)** | Framework bridge: register PSR-4 (Composer `ClassLoader`) + register providers + publish/boot. `LaravelLoaderAdapter` ships first. |
| **`ExtensionManager`** | Orchestrates the lifecycle: `activate/deactivate/install/remove/update` + hooks + events. |
| **`ManagementServiceProvider`** | Built on `laranail/package-tools`' `PackageServiceProvider`: `configurePackage()` (namespaced config, migrations, commands) + `packageRegistered()` (loader + state bindings) + `packageBooted()` (register active extensions). |
| **CLI commands** | `laranail::package-management.{list,enable,disable,install,update,remove,discover,cache,install-from}` (+ `package-management:*` aliases). |
| **`Extensions` facade / helpers** | Ergonomic runtime API (`extension()`, `is_extension_active()`, `extension_path()`, `extension_vite()`) + the `Extensions` facade (`query()`, `graph()`, `dependents()`, lifecycle). |

## Roles vs frameworks (two orthogonal axes)

- **Role** = *how it's consumed* (package / module / plugin) — determined by which manifests are
  present. One repo may be all three.
- **Framework** = *what it's built on* (vanilla / laravel / lumen / …) — determined by the scaffolder
  flavor; drives which `LoaderAdapter` can host it. A vanilla package needs no runtime at all; a
  laravel module/plugin loads via the Laravel adapter.

See [manifests.md](manifests.md) for how roles are encoded, and [extending.md](extending.md) for how a
new framework adapter is added.

## Relationship to the scaffolder's existing module runtime

`package-scaffolder` currently contains a module runtime (`FileRepository`, `ModuleManifest`,
`FileActivator`, `module:*`). That overlaps this package's job and is the long-term extraction target:
this package is the clean, framework-adaptable home for loading. The scaffolder keeps its runtime for
now; a future phase has it depend on / delegate to `package-management`. This avoids a risky big-bang
extraction while establishing the correct architecture.

[← Docs index](../README.md#documentation)
