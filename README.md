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
php artisan vendor:publish --provider="Simtabi\Laranail\Package\Management\Providers\ManagementServiceProvider" --tag=package-management-config
```

Drop generated extensions under `platform/{packages,modules,plugins}/` and the loader discovers them.

## Documentation

- [Architecture](docs/ARCHITECTURE.md) — the discover → resolve → register → activate → wire pipeline, and the diagram.
- [Manifests](docs/manifests.md) — the authoritative `composer.json` / `module.json` / `plugin.json` schemas (the contract with the scaffolder).
- [Installation](docs/installation.md) · [Usage](docs/usage.md) · [Lifecycle](docs/lifecycle.md) · [Extending (framework adapters)](docs/extending.md) · [Features](docs/features.md)

## Credits

- [Simtabi LLC](https://github.com/simtabi)
- [Imani Manyara](https://github.com/imanimanyara)

## License

The MIT License (MIT). See [LICENSE](LICENSE).
