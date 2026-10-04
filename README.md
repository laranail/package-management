# laranail/package-management

[![Tests](https://github.com/laranail/package-management/actions/workflows/tests.yml/badge.svg)](https://github.com/laranail/package-management/actions/workflows/tests.yml)
[![Static analysis](https://github.com/laranail/package-management/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/laranail/package-management/actions/workflows/static-analysis.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

`laranail/package-management` is not on Packagist, so there is no registry-version badge to show — [Install](#install) covers the VCS route.

> Runtime loader / manager for the laranail scaffolding ecosystem — discovers generated packages/modules/plugins from their manifests, resolves load order (topological, semver-guarded), registers their PSR-4 autoloading + service providers at runtime (no `composer dump`), and activates them through a guarded install/update/remove lifecycle.

The run-time counterpart to [`laranail/package-scaffolder`](https://opensource.simtabi.com/documentation/laranail/package-scaffolder/) (which *generates* the artifacts this loads). Built on `laranail/package-tools` + `laranail/console`. Targets PHP `^8.4.1 || ^8.5` on Laravel `^13`.

## Install

```bash
composer require laranail/package-management
```

## Quick start guide and usage

### Getting started

1. Optionally publish the config to customise discovery paths, the cache and the activation store:

   ```bash
   php artisan vendor:publish --tag=laranail::package-management-config
   ```

2. If you set `PACKAGE_MANAGEMENT_STORE=database`, run the auto-loaded migration:

   ```bash
   php artisan migrate
   ```

3. Place extensions under `platform/packages/`, `platform/modules/` or `platform/plugins/`.

### Usage

```bash
# With a module dropped in at platform/modules/Blog/ (module.json alias: "blog")
php artisan laranail::package-management.discover
php artisan laranail::package-management.list          # blog · module · 1.0.0 · inactive
php artisan laranail::package-management.install blog  # activate, migrate, publish, seed
```

Then verify it loaded, from code:

```php
use Simtabi\Laranail\Package\Management\Facades\Extensions;

is_extension_active('blog');                 // true
Extensions::query()->active()->ids();        // ['blog']
extension('blog')->version;                  // '1.0.0'
```

The full walkthrough is in [Getting started](docs/getting-started.md); everything else is in the [documentation index](#documentation).

## <a name="documentation"></a>Documentation

Full documentation is at **[opensource.simtabi.com/documentation/laranail/package-management](https://opensource.simtabi.com/documentation/laranail/package-management/)** — discovery, load-order resolution, runtime registration, the guarded lifecycle, VCS installs, safety, and configuration.

## Contributing & security

Issues and PRs are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md). Report vulnerabilities per
[SECURITY.md](SECURITY.md) (opensource@simtabi.com); participation follows the [Code of Conduct](CODE_OF_CONDUCT.md).

## License

MIT © Simtabi LLC. See [LICENSE](LICENSE).
