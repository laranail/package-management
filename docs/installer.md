# VCS installer

Install an extension straight from a VCS provider (GitHub · GitLab · Bitbucket) into
`platform/{packages|modules|plugins}/{name}` (lowercase folder — `Str::slug()` of the manifest name) and
run it through the existing lifecycle, with a **rollback stack** so a failed install leaves no orphaned
files or tables.

## 1. Contracts + drivers (pluggable, Manager-based)

```php
// Contracts\SourceDriver
interface SourceDriver
{
    public function supports(RepositoryRef $ref): bool;          // claim a provider/URL
    public function download(RepositoryRef $ref, string $toDir): string; // → path to fetched archive
}
```

`RepositoryRef` — an immutable VO parsed from the user input:

```php
final readonly class RepositoryRef
{
    // provider: 'github'|'gitlab'|'bitbucket'; owner/repo; ref = branch|tag|commit (default 'HEAD'); token?
    public string $provider, $owner, $repo, $ref;
    public ?string $token;
    public static function parse(string $url, ?string $ref, ?string $token, string $default): self;
}
```

Drivers resolved by a Laravel Manager (so hosts add providers via `extend()`):

```php
final class SourceDriverManager extends Illuminate\Support\Manager
{
    public function getDefaultDriver(): string;      // config installer.default_provider
    protected function createGithubDriver(): SourceDriver;    // ships first
    protected function createGitlabDriver(): SourceDriver;    // ships
    protected function createBitbucketDriver(): SourceDriver; // ships
    public function forRef(RepositoryRef $ref): SourceDriver; // pick by ref->provider / supports()
}
```

- **GitHub** driver: `https://api.github.com/repos/{o}/{r}/tarball/{ref}` (or codeload), `Authorization:
  Bearer {token}` for private repos, follows redirects, streams to a temp file (`Http::withToken`).
- **GitLab** / **Bitbucket** drivers: the equivalent archive endpoints + their token headers.
- HTTP via `Illuminate\Http\Client` with `installer.timeout`; **tokens are never logged**.

## 2. Installer service + the rollback stack

```php
final class ExtensionInstaller
{
    public function install(
        RepositoryRef $ref,
        ?string $asRole = null,
        bool $force = false,
        ?callable $confirmOverwrite = null,
    ): Extension;
}
```

Lifecycle (each step registers an **undo** on a `RollbackStack`; any throw unwinds in reverse):

| # | Step | Undo pushed |
|---|---|---|
| 1 | **download** archive → temp dir (driver) | delete temp dir |
| 2 | **verify** (non-empty, size cap, is a valid zip/tar) | — |
| 3 | **extract** to a temp working dir | delete working dir |
| 4 | **detect role** — from the manifest (`plugin.json`→plugin, `module.json`→module, else package) or `--as` override; resolve target `platform/{role}s/{name}` where `name = Str::slug(manifest name)` (**lowercase**) | — |
| 5 | **guard** — if target exists: `--force` overwrites; else in an interactive TTY **prompt** "overwrite / skip"; else (non-interactive) refuse. Ensure the target is inside the configured roots | delete target dir |
| 6 | **move** working dir → target | delete target dir |
| 7 | **register** — `discover()` (rebuild cache) so the loader sees it | forget/rebuild cache |
| 8 | **install lifecycle** — `ExtensionManager::install(id)` (activate + migrate + publish assets + seed settings) | `ExtensionManager::remove(id)` **+ migration rollback** |
| — | success → **commit** (clear the stack, keep the target) | — |

On any failure the stack unwinds: rolled-back migrations, removed target dir, deleted temp/working dirs
→ **no orphaned files or tables**. Steps 7–8 reuse the existing manager; the installer adds only
fetch/extract/place + orchestration.

## 3. Config

```php
'installer' => [
    'default_provider' => env('PACKAGE_MANAGEMENT_VCS', 'github'),
    'tokens' => [
        'github'    => env('GITHUB_TOKEN'),
        'gitlab'    => env('GITLAB_TOKEN'),
        'bitbucket' => env('BITBUCKET_TOKEN'),
    ],
    'timeout' => 60,              // seconds
    'max_bytes' => 104857600,     // 100 MB archive cap
    'rollback_migrations_on_remove' => false, // data-safety default (see §5)
],
```

## 4. CLI

```
php artisan laranail::package-management.install-from <url> [--ref=main] [--as=module] [--token=…] [--force]
```
(+ `package-management:install-from` alias). `<url>` accepts `owner/repo`, `github.com/owner/repo`,
`https://github.com/owner/repo(.git)`, and the GitLab/Bitbucket equivalents.

## 5. Lifecycle completion (bundled with the installer)

- **`RunsMigrations::rollbackMigrations(Extension)`** capability → `LaravelLoaderAdapter` rolls back the
  extension's own `database/migrations` (path-scoped). Used by the rollback stack (always) and by
  `remove()` **only when** `installer.rollback_migrations_on_remove = true` (default **false** — removing
  a plugin must not destroy user data by surprise; opt-in).
- **`updating` / `updated`** added to `update()` — a `ExtensionUpdating`/`ExtensionUpdated` event pair +
  the duck-typed `updating`/`updated` hook methods (completes the hook set flagged earlier).

## 6. Botble delta — kept vs improved

| Aspect | Botble | Here |
|---|---|---|
| Source | GitHub-only, hardcoded in the updater | **`SourceDriver` contract + Manager** — GitHub/GitLab/Bitbucket, host-pluggable |
| Failure handling | best-effort; can leave partial files | **rollback stack** — atomic-ish, no orphans |
| Role/target | fixed `plugins/` | **role inferred from manifest** (or `--as`), into `platform/{role}s` |
| State | DB settings table required | reuses our **file-or-Eloquent** store; no new coupling |
| Auth | license-server tokens | **per-provider VCS tokens** (env), never logged |

## 7. Behavior notes

- **Scope:** all three drivers (GitHub, GitLab, Bitbucket) ship.
- **Existing target:** `--force` overwrites; otherwise the CLI **prompts** (overwrite / skip) in an
  interactive TTY, and a non-interactive run without `--force` refuses. Either way the rollback stack
  guards the overwrite (the old target is backed up and restored if the install fails).
- **Folder naming:** lowercase `platform/{role}s/{name}` (`Str::slug()` of the manifest name).
- **Source layout:** `src/Installer/{RepositoryRef,SourceDriverManager,ExtensionInstaller,RollbackStack}.php`
  + `src/Installer/Drivers/{Github,Gitlab,Bitbucket}SourceDriver.php`, `src/Contracts/SourceDriver.php`,
  `src/Commands/InstallFromVcsCommand.php`, the config `installer` block, and the provider bindings.
- **Tests:** a `RepositoryRef::parse()` URL matrix; driver endpoint + token headers (faked `Http`); a full
  install against a local tarball fixture (offline); a rollback test proving a mid-install failure leaves
  no target dir + no migrated table; and an opt-in live smoke (skipped unless
  `PACKAGE_MANAGEMENT_LIVE_INSTALL_TEST` is set).

[← Docs index](../README.md#documentation)
