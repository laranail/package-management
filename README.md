# Package Management

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

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

## Install

```bash
composer require laranail/package-management
php artisan vendor:publish --tag=laranail::package-management-config
```

Built on [`laranail/package-tools`](https://opensource.simtabi.com/package-tools/) — config resolves under
the vendor-namespaced key `config('laranail.package-management.*')`.

Drop generated extensions under `platform/{packages,modules,plugins}/` and the loader discovers them.

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
- [Features](docs/features.md) — the full capability set + phasing
- [Release](docs/release.md) — how releases are cut

## Credits

- [Simtabi LLC](https://github.com/simtabi)
- [Imani Manyara](https://github.com/imanimanyara)

## License

The MIT License (MIT). See [LICENSE](LICENSE).
