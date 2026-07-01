# Contributing

Contributions are welcome via pull requests.

## Process

1. Fork the project and create a branch.
2. Code, test, commit and push.
3. Open a pull request describing your change.

## Guidelines

- Style: run `composer pint-fix` (Laravel Pint preset, `declare(strict_types=1)`).
- Static analysis: `composer phpstan` must pass.
- Tests: `composer test` must pass; add tests for new behavior.
- Keep the manifest schemas in `docs/manifests.md` in sync with `laranail/package-scaffolder`.
