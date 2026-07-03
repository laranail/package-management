# Changelog

All notable changes to `laranail/package-management` will be documented in this file.

## Next

First working release of the runtime loader/manager for scaffolder-generated
packages/modules/plugins (discover → resolve → register → activate → wire).

### Added

- **Core loader:** manifest discovery (`composer.json` / `module.json` / `plugin.json`),
  topological dependency resolution, runtime PSR-4 + provider registration, and a compiled
  manifest cache (`…​.discover` / `…​.cache`).
- **Activation stores:** file (default, zero-DB) and Eloquent (`ExtensionState` model +
  Actions → Service → Repository, `ExtensionState` facade); config-driven table + connection.
- **Framework adapters** behind `LoaderAdapter`: Laravel (ships), Lumen, and a hardened
  Symfony runtime adapter (container injection, interface aliases, duck-typed `register()`/
  `boot()`, compiled-container no-op).
- **Lifecycle:** install / update / remove with dependency + semver `require` + `minimum_core_version`
  guards; migrations run + optional rollback; asset publishing; settings seeding. Full pre/post
  **event pairs** and duck-typed per-extension **hooks**.
- **VCS installer:** install from GitHub / GitLab / Bitbucket (`…​.install-from`) with a rollback
  stack (no orphaned files/tables).
- **Extensibility:** macroable manager + `pipe()` DSL, `ManifestPipeline`, a caching state-repository
  decorator, and the spyable `Extensions` facade.
- **Query API:** `Extensions::query()` / `graph()` / `dependents()`.
- **Host seams:** the `menu` manifest field + `ContributesNavigation` contract; `extension_vite()`
  for per-extension Vite assets; an opt-in web management UI.
- **CLI:** `laranail::package-management.*` (list/enable/disable/discover/cache/install/update/remove/
  install-from), each with a `package-management:*` alias.
- Helpers: `extension()`, `is_extension_active()`, `extension_path()`, `extension_vite()`.

### Notes

- Built on `laranail/package-tools` + `laranail/console`; config is vendor-namespaced under
  `config('laranail.package-management.*')`. Comprehensive docs under `docs/` (+ ADR 0001).
