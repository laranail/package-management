# Release

Releases are **tag-driven**. Cutting `vX.Y.Z` triggers `.github/workflows/release.yml`, which builds a
CycloneDX SBOM, extracts that version's `CHANGELOG.md` section as the release body, and publishes the
GitHub release.

## Steps

1. Update `CHANGELOG.md`: move `## Next` entries under a new `## [X.Y.Z] - YYYY-MM-DD` heading.
2. Commit on `main` (`git config user.email imanimanyara@users.noreply.github.com`).
3. Tag + push:
   ```bash
   git tag vX.Y.Z
   git push origin main --tags
   ```
4. CI (`release.yml`) publishes the GitHub release + attaches the SBOM.

## Versioning

Semver. Breaking changes to the public API (the `Extensions` facade, the `Contracts\*` interfaces, the
manifest schemas in [manifests.md](manifests.md)) are a major bump and must be documented in
[../UPGRADING.md](../UPGRADING.md). Keep the manifest schemas in lockstep with
`laranail/package-scaffolder` — they are the shared contract.

[← Docs index](../README.md#documentation)
