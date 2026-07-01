# Features

The full capability set of the runtime loader. Legend: **✅ core** (delivered in the first working
core), **🔶 planned** (specified now, built incrementally), **🧭 future**.

## Discovery & registration
- ✅ Scan `platform/{packages,modules,plugins}/*` (configurable paths) and read all manifests.
- ✅ Normalize `composer.json` / `module.json` / `plugin.json` into one `Extension` VO.
- ✅ Runtime PSR-4 registration via Composer's `ClassLoader` — no per-extension `composer dump`.
- ✅ Register each active extension's service provider(s), skipping missing classes (never fatal).
- ✅ Compiled manifest cache (`bootstrap/cache/laranail-extensions.php`): stores the discovered set
  (activation applied fresh each request), built/cleared via the `…​.cache` command.

## Dependency management
- ✅ Topological sort over `require` for deterministic load order.
- ✅ Cycle + missing-dependency detection (fail loudly at activate time).
- ✅ `minimum_core_version` guard.
- 🔶 Version-constraint checks on `require` (semver ranges).

## Activation lifecycle
- ✅ Activation-state store behind an interface — `FileActivationStore` (JSON) default.
- ✅ `DatabaseActivationStore` adapter (state table + migration) for DB-backed projects; reads degrade
  gracefully until the table is migrated.
- ✅ `activate` / `deactivate` with dependency + reverse-dependency guards.
- ✅ Per-extension lifecycle hook (`LifecycleHook::activated/deactivated`), declared via the manifest
  `hook` FQCN and resolved from the container.
- ✅ Events: `ExtensionActivated` / `ExtensionDeactivated` / `ExtensionInstalled` / `ExtensionUpdated`.
- ✅ `install` (activate + run the extension's own `database/migrations`) / `update` (run pending
  migrations) via the optional `RunsMigrations` adapter capability (Laravel ships it; other adapters
  degrade to activate-only).
- 🔶 `remove` (migration rollback — needs per-extension batch tracking) + asset (un)publish + the
  extended hook set (`installed/removed/updating/updated`).

## Backend glue
- ✅ Service-provider registration (routes/config/commands come via the provider).
- 🔶 Convention wiring: web/api/admin routes, migrations, translations, config publish.
- 🔶 Permissions/policies registration; admin-menu contribution.
- ✅ Artisan CLI: `laranail::package-management.{list,enable,disable,discover}` (+ `cache`, `install`,
  `remove` as those land) with `module:*`/`plugin:*` aliases.

## Frontend glue
- 🔶 View + Blade-component namespace registration per extension.
- 🔶 Vite asset publishing + loading (`@vite`), per-extension build dirs.
- 🧭 Theme layer (like Botble `themes/`).

## Framework adapters
- ✅ `LoaderAdapter` interface + `LaravelLoaderAdapter` (autoload via a shared trait).
- ✅ `LumenLoaderAdapter` — works against the bare container contract (uses `register()` when the app
  exposes it, else instantiates the provider and calls `register()`/`boot()` itself).
- 🧭 `SymfonyLoaderAdapter` (bundle registration) — proves the abstraction.

## Management surface
- ✅ `Extensions` facade + helpers (`extension()`, `is_extension_active()`, `extension_path()`).
- 🔶 Query API: list by role, enabled/disabled, dependency graph.
- 🧭 Admin UI panel to list/enable/disable/update extensions (Botble-style).
- 🧭 Marketplace/installer (download + extract into `platform/plugins/`).

## Non-goals
- Generation/scaffolding (that's `laranail/package-scaffolder`).
- Being a CMS — this is the loader substrate a CMS/app builds on.

[← Docs index](../README.md#documentation)
