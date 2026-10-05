# Changelog

All notable changes to `laranail/package-management` are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **Views also answer to the canonical `laranail/package-management::` namespace**, over the same
  files as `laranail-package-management::`, which keeps working. The extensions UI renders through
  the canonical name.
- **`NamingConventionTest`** reads the command and view registries of the booted application
  through package-tools' `AssertsRegisteredNames`.

### Changed

- **Requires `laranail/console ^0.1.5` and `laranail/package-tools ^0.1.3`**, the first releases
  with deprecated command aliases and the dual view namespace this package now relies on.
- **`docs/architecture.md` names `laranail::package-scaffolder.new`** as the command that generates
  an artifact. It said `make:artifact`, which `laranail/package-scaffolder` now keeps only as a
  deprecated alias that prints a warning.

### Deprecated

- **The nine `package-management:<verb>` command aliases** (`list`, `enable`, `disable`, `discover`,
  `cache`, `install`, `remove`, `update`, `install-from`). Each is a bare name in Artisan's flat
  command registry. They still run the same command, and now print one line naming the
  `laranail::package-management.<verb>` replacement before anything else. Removal no earlier than
  the next minor after 0.1.

## [0.1.0] - 2026-07-11

Initial public release.

[Unreleased]: https://github.com/laranail/package-management/compare/v0.1.0...HEAD
