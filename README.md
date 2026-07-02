# laranail/package-management

[![Latest version on Packagist](https://img.shields.io/packagist/v/laranail/package-management.svg)](https://packagist.org/packages/laranail/package-management)
[![Tests](https://github.com/laranail/package-management/actions/workflows/tests.yml/badge.svg)](https://github.com/laranail/package-management/actions/workflows/tests.yml)
[![Static analysis](https://github.com/laranail/package-management/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/laranail/package-management/actions/workflows/static-analysis.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Runtime **loader / manager** for the laranail scaffolding ecosystem. Drop it into any project and it
discovers, registers, activates, and wires generated **packages · modules · plugins** into the host
app's **backend and frontend** — the runtime counterpart to
[`laranail/package-scaffolder`](https://opensource.simtabi.com/package-scaffolder/) (which *generates*
those extensions).

- **Generation** (author-time) → `laranail/package-scaffolder`.
- **Loading** (run-time) → this package.

One generated repo can be consumed three ways by the manifests it carries:

| Role | Manifest | Loaded by |
|---|---|---|
| package | `composer.json` | Composer autoload / Laravel auto-discovery (no runtime needed) |
| module | `module.json` | this loader's module runtime (activation-gated) |
| plugin | `plugin.json` | this loader's plugin runtime / host ecosystem |

### Domain model

The first-class noun is the **extension** — the role-neutral umbrella over the three roles above
(`Extension->role` is `package` | `module` | `plugin`). "package-management" is the *activity* (this
package manages extensions), not a domain type; "package" is reserved for the role. Hence `Extension*`
everywhere. See [ADR 0001](docs/adr/0001-extension-as-the-abstraction.md) for the full rationale.

## Requirements

- PHP `^8.4.1 || ^8.5`
- Laravel `^13` (the shipping adapter; Lumen and Symfony via a `LoaderAdapter` — see
  [extending.md](docs/extending.md))

## Installation

```bash
composer require laranail/package-management
php artisan vendor:publish --tag=laranail::package-management-config
```

The `ManagementServiceProvider` is auto-discovered. Built on
[`laranail/package-tools`](https://opensource.simtabi.com/package-tools/) — config resolves under the
vendor-namespaced key `config('laranail.package-management.*')`. For the database activation store, also
run `php artisan migrate`.

## Quick start

Drop generated extensions (from `laranail/package-scaffolder`) under `platform/{packages,modules,plugins}/`,
then discover + activate them:

```bash
php artisan laranail::package-management.discover        # rescan + rebuild the manifest cache
php artisan laranail::package-management.list            # id · role · version · state
php artisan laranail::package-management.install acme/blog   # activate + migrate + publish assets + seed settings
```

```php
use Simtabi\Laranail\Package\Management\Facades\Extensions;

Extensions::all();                       // list<Extension>
Extensions::enable('acme/blog');         // dependency-guarded activation
is_extension_active('acme/blog');        // helper
```

## Documentation

Hosted at [`opensource.simtabi.com/package-management/docs/`](https://opensource.simtabi.com/package-management/docs/)
(product page: [`opensource.simtabi.com/package-management/`](https://opensource.simtabi.com/package-management/)).
The same pages live under [`docs/`](docs/):

- [Installation](docs/installation.md) — requirements, install, platform layout
- [Configuration](docs/configuration.md) — discovery paths, compiled cache, activation store
- [Usage](docs/usage.md) — the CLI + the programmatic API
- [Architecture](docs/architecture.md) — the discover → resolve → register → activate → wire pipeline + diagram
- [Manifests](docs/manifests.md) — the authoritative `composer.json` / `module.json` / `plugin.json` schemas (the scaffolder contract)
- [Lifecycle](docs/lifecycle.md) — activation states, transitions, hooks, dependency ordering
- [Extending](docs/extending.md) — framework adapters (Laravel / Lumen / Symfony)
- [Installer](docs/installer.md) — install extensions from GitHub / GitLab / Bitbucket
- [Features](docs/features.md) — the full capability set + phasing
- [Release](docs/release.md) — how releases are cut

## Credits

- [Simtabi LLC](https://github.com/simtabi)
- [Imani Manyara](https://github.com/imanimanyara)

## License

The MIT License (MIT). See [LICENSE](LICENSE).
