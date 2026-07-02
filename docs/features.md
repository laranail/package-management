# Features

The full capability set of the runtime loader. Legend: **✅ core** (delivered in the first working
core), **🔶 planned** (specified now, built incrementally), **🧭 future**.

## Foundation
- ✅ Built on `laranail/package-tools` (`PackageServiceProvider` + fluent `Package` builder) and
  `laranail/console` (namespaced `laranail::…` command base).
- ✅ Vendor-namespaced config via package-tools — `config('laranail.package-management.*')` — with
  auto-loaded/auto-run migrations and declarative command registration.
- ✅ `php artisan about` section (discovered / active / modules / plugins counts + active store).

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
- ✅ Eloquent-backed database store (`store = 'database'`): a properly-layered subsystem — **Facade →
  Manager → Actions (writes) / Service (reads) → Repository → `ExtensionState` model** over a rich state
  table (name, is_active, version, settings, installed_at, activated_at) with a factory + seeder; reads
  degrade gracefully until the table is migrated; installed version is recorded on `install` via the
  `RecordsInstall` store capability. Exposed to host apps as the `ExtensionState` facade (+ settings).
- ✅ `activate` / `deactivate` with dependency + reverse-dependency guards.
- ✅ Per-extension lifecycle hook (`LifecycleHook::activated/deactivated`), declared via the manifest
  `hook` FQCN and resolved from the container.
- ✅ Events: `ExtensionActivated` / `ExtensionDeactivated` / `ExtensionInstalled` / `ExtensionUpdated` /
  `ExtensionRemoved`.
- ✅ `install` (activate + run the extension's own `database/migrations` + publish its `public/` assets
  to `public/vendor/{slug}` + seed manifest **default settings** into the state, defaults filling gaps)
  / `update` (run pending migrations), via optional `RunsMigrations` / `PublishesAssets` /
  `RecordsInstall` / `SeedsSettings` capabilities (Laravel ships all; other adapters degrade gracefully).
- ✅ `remove` (uninstall): deactivate + unpublish assets + forget the management-state row, while
  **preserving** the extension's own database tables (removing a plugin must not destroy user data);
  fires `ExtensionRemoved`.
- ✅ Per-extension hooks — `activated`/`deactivated`/`installed`/`removed`, **duck-typed** so a
  scaffolder-generated hook needs no dependency on the loader; `Contracts\LifecycleHook` /
  `Contracts\InstallHook` are the optional type-safe path.
- ✅ `updating` / `updated` hooks + `ExtensionUpdating` / `ExtensionUpdated` events on `update()`.
- ✅ Optional migration rollback on remove (`RunsMigrations::rollbackMigrations`; opt-in via
  `installer.rollback_migrations_on_remove`, default off for data-safety).

## Backend glue
- ✅ Service-provider registration (routes/config/commands come via the provider).
- 🔶 Convention wiring: web/api/admin routes, migrations, translations, config publish.
- 🔶 Permissions/policies registration; admin-menu contribution.
- ✅ Artisan CLI: `laranail::package-management.{list,enable,disable,discover,cache,install,remove}` —
  each with a `package-management:<verb>` plain-colon alias.

## Frontend glue
- 🔶 View + Blade-component namespace registration per extension.
- 🔶 Vite asset publishing + loading (`@vite`), per-extension build dirs.
- 🧭 Theme layer (like Botble `themes/`).

## Framework adapters
- ✅ `LoaderAdapter` interface + `LaravelLoaderAdapter` (autoload via a shared trait).
- ✅ `LumenLoaderAdapter` — works against the bare container contract (uses `register()` when the app
  exposes it, else instantiates the provider and calls `register()`/`boot()` itself).
- ✅ `SymfonyLoaderAdapter` — runtime PSR-4 + sets each provider as a Symfony container service
  (`Container::set()`); Symfony compiles its container at build time, so this is the runtime surface
  (a compiler pass is the build-time route for richer wiring).

## Management surface
- ✅ `Extensions` facade + helpers (`extension()`, `is_extension_active()`, `extension_path()`).
- ✅ Opt-in web **management UI** (`config ui.enabled`): a Blade page that lists extensions and drives
  the full lifecycle — enable / disable / install / update / remove **and install-from-VCS** (CLI↔UI
  parity) — at a configurable prefix + middleware.
- ✅ Query API: `Extensions::query()->role('plugin')->active()->get()` (fluent, immutable) plus
  `graph()` (require adjacency) and `dependents(id)` (reverse deps).
- ✅ **VCS installer** — install from GitHub / GitLab / Bitbucket (pluggable `SourceDriver`s) with a
  rollback stack (no orphaned files/tables): `laranail::package-management.install-from <url>`. See
  [installer.md](installer.md).

## Non-goals
- Generation/scaffolding (that's `laranail/package-scaffolder`).
- Being a CMS — this is the loader substrate a CMS/app builds on.

[← Docs index](../README.md#documentation)
