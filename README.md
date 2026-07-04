# laranail/package-management

[![Latest version on Packagist](https://img.shields.io/packagist/v/laranail/package-management.svg)](https://packagist.org/packages/laranail/package-management)
[![Tests](https://github.com/laranail/package-management/actions/workflows/tests.yml/badge.svg)](https://github.com/laranail/package-management/actions/workflows/tests.yml)
[![Static analysis](https://github.com/laranail/package-management/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/laranail/package-management/actions/workflows/static-analysis.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Runtime **loader / manager** for the laranail scaffolding ecosystem — the run-time counterpart to
[`laranail/package-scaffolder`](https://github.com/laranail/package-scaffolder) (which *generates* the
artifacts this loads). Drop it into a Laravel app and it:

- **discovers** generated packages/modules/plugins from their manifests;
- **resolves** their load order (topological over `require`, with semver + `minimum_core_version` guards);
- **registers** their PSR-4 autoloading + service providers at runtime — no `composer dump`;
- **activates** them through a guarded lifecycle (install / update / remove, with event pairs + hooks);
- **wires** their backend + frontend into the host — and can install them straight from a VCS repo.

Built on [`laranail/package-tools`](https://github.com/laranail/package-tools) +
[`laranail/console`](https://github.com/laranail/console); config resolves under the vendor-namespaced key
`config('laranail.package-management.*')`. Targets PHP `^8.4.1 || ^8.5` on Laravel `^13`.

## Install

```bash
composer require laranail/package-management
```

The `ManagementServiceProvider` is auto-discovered. Publish the config if you want to customise it, and
run the migration if you use the database activation store:

```bash
php artisan vendor:publish --tag=laranail::package-management-config
php artisan migrate   # only for the database activation store
```

## Quick start

Drop generated extensions under `platform/{packages,modules,plugins}/`, then discover + activate them:

```bash
php artisan laranail::package-management.discover          # rescan + rebuild the manifest cache
php artisan laranail::package-management.list              # id · role · version · state
php artisan laranail::package-management.install acme/blog # activate + migrate + publish assets + seed settings
php artisan laranail::package-management.install-from acme/blog --ref=v1.2.0   # …or install straight from a VCS repo
```

```php
use Simtabi\Laranail\Package\Management\Facades\Extensions;

Extensions::all();                                  // list<Extension>
Extensions::enable('acme/blog');                    // dependency- + version-guarded activation
Extensions::query()->role('plugin')->active()->get();
is_extension_active('acme/blog');                   // helper
```

See [Getting started](docs/getting-started.md) for the full walkthrough.

### Roles

One generated repo is consumable in up to three roles, each keyed by a manifest — the loader's core
mental model:

| Role | Manifest | Loaded by |
|---|---|---|
| **package** | `composer.json` | Composer autoload / Laravel auto-discovery (no runtime needed) |
| **module** | `module.json` | this loader's module runtime (activation-gated) |
| **plugin** | `plugin.json` | this loader's plugin runtime / host ecosystem |

The role-neutral umbrella over all three is an **extension** (`Extension->role`) — see
[Architecture](docs/architecture.md#why-extension-is-the-abstraction) for why that term.

## Safety

- **Never fatals a boot.** Provider registration is `class_exists`-guarded and malformed manifests are
  silently skipped, so a stale manifest or an un-dumped autoload is ignored, not fatal.
- **Atomic installs.** The VCS installer wraps every step in a rollback stack — a failed install leaves no
  orphaned files, migrated tables, or activation state.
- **No host coupling.** Discovery paths, the compiled-cache path, the activation file/table/connection, and
  the UI prefix are all config; the only runtime dependencies are `illuminate/*` +
  `laranail/package-tools` / `laranail/console`.
- **Zero dependency on the loader.** Generated extensions need no `require` on this package — lifecycle
  hooks are duck-typed and the loader is a `suggest` only.

## <a name="documentation"></a>Documentation

### Guides

- [Installation](docs/installation.md) — requirements, install, the `platform/` layout.
- [Getting started](docs/getting-started.md) — discover → enable → install your first extension.
- [Configuration](docs/configuration.md) — discovery paths, compiled cache, activation store, installer, UI.
- [Architecture](docs/architecture.md) — the discover → resolve → register → activate → wire pipeline.
- [Manifests](docs/manifests.md) — the `composer.json` / `module.json` / `plugin.json` schemas (the scaffolder contract).
- [Lifecycle](docs/lifecycle.md) — activation states, transitions, hooks, dependency ordering.
- [Comparison](docs/comparison.md) — vs `nwidart/laravel-modules` and Botble plugin-management.
- [Release](docs/release.md) — how a version is cut.

### Reference

- [Commands](docs/tools/commands.md) — the full `laranail::package-management.*` CLI.
- [Facade & helpers](docs/tools/facade.md) — the `Extensions` / `ExtensionState` API, query + graph, helpers.
- [Adapters](docs/tools/adapters.md) — framework adapters (Laravel / Lumen / Symfony) + the CompilerPass recipe.
- [Installer](docs/tools/installer.md) — install from GitHub / GitLab / Bitbucket.
- [Extensibility](docs/tools/extensibility.md) — macros, manifest pipeline, caching decorator, events, spy seam.
- [Host integration](docs/tools/host-integration.md) — routes/policies/views, the menu seam, Vite assets.

### Recipes

- [Install from a VCS repo](docs/recipes/install-from-vcs.md)
- [Use the database activation store](docs/recipes/database-activation-store.md)
- [Compile the manifest cache for production](docs/recipes/production-manifest-cache.md)
- [Add a framework adapter](docs/recipes/add-a-framework-adapter.md)
- [Write a lifecycle hook](docs/recipes/write-a-lifecycle-hook.md)
- [Contribute an admin-menu entry](docs/recipes/contribute-an-admin-menu.md)
- [Enable the management UI](docs/recipes/enable-the-management-ui.md)

### Project

- [Upgrading](UPGRADING.md) — breaking-change migration notes.
- [Changelog](CHANGELOG.md) — release history.

Online docs: <https://opensource.simtabi.com/package-management/docs/>.

> Requires PHP `^8.4.1 || ^8.5` on Laravel `^13`. The database activation store also needs
> `php artisan migrate`.

## Stability

`laranail/package-management` is **pre-1.0 (0.x)**. The public API — the `Extensions` and `ExtensionState`
facades, the `Contracts\*` interfaces, and the manifest schemas ([Manifests](docs/manifests.md)) — is what
[UPGRADING.md](UPGRADING.md) tracks; breaking changes are documented there per release, with a clear
before/after. It follows [SemVer](https://semver.org) once `1.0` lands.

## Local development

```bash
composer install
composer test        # phpunit --no-coverage (composer test-coverage for coverage)
composer lint        # pint + phpstan (level 8) + rector --dry-run
composer audit       # composer audit (security advisories)
```

This package uses **PHPUnit** (a deliberate, recorded deviation from the laranail Pest default).

## Sister packages

- [`laranail/package-scaffolder`](https://github.com/laranail/package-scaffolder) — author-time generator that scaffolds the packages/modules/plugins this loader runs.
- [`laranail/package-tools`](https://github.com/laranail/package-tools) — the `PackageServiceProvider` base + fluent `Package` builder this package is built on.
- [`laranail/console`](https://github.com/laranail/console) — the command base enabling the `laranail::` namespaced Artisan commands.

## Community

- [Issues](https://github.com/laranail/package-management/issues) — bug reports.
- [Discussions](https://github.com/laranail/package-management/discussions) — ideas, questions, proposals.

## Contributing & security

- [CONTRIBUTING.md](CONTRIBUTING.md) — workflow, coding standards, command naming.
- [SECURITY.md](SECURITY.md) — how to report a vulnerability.
- [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) — community expectations.

## License

MIT © Simtabi LLC. See [LICENSE](LICENSE).
