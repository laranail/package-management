# laranail/package-management

[![Latest version on Packagist](https://img.shields.io/packagist/v/laranail/package-management.svg)](https://packagist.org/packages/laranail/package-management)
[![Tests](https://github.com/laranail/package-management/actions/workflows/tests.yml/badge.svg)](https://github.com/laranail/package-management/actions/workflows/tests.yml)
[![Static analysis](https://github.com/laranail/package-management/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/laranail/package-management/actions/workflows/static-analysis.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

> Runtime loader / manager for the laranail scaffolding ecosystem — discovers generated packages/modules/plugins from their manifests, resolves load order (topological, semver-guarded), registers their PSR-4 autoloading + service providers at runtime (no `composer dump`), and activates them through a guarded install/update/remove lifecycle.

The run-time counterpart to [`laranail/package-scaffolder`](https://opensource.simtabi.com/documentation/laranail/package-scaffolder/) (which *generates* the artifacts this loads). Built on `laranail/package-tools` + `laranail/console`. Targets PHP `^8.4.1 || ^8.5` on Laravel `^13`.

## Install

```bash
composer require laranail/package-management
```

## Documentation

Full documentation is at **[opensource.simtabi.com/documentation/laranail/package-management](https://opensource.simtabi.com/documentation/laranail/package-management/)** — discovery, load-order resolution, runtime registration, the guarded lifecycle, VCS installs, safety, and configuration.

## Contributing & security

Issues and PRs are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md). Report vulnerabilities per
[SECURITY.md](SECURITY.md) (opensource@simtabi.com); participation follows the [Code of Conduct](CODE_OF_CONDUCT.md).

## License

MIT © Simtabi LLC. See [LICENSE](LICENSE).
