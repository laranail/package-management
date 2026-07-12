# Commands

The `laranail::package-management.*` Artisan commands drive discovery and the activation lifecycle. Every
command also has a plain-colon alias (`package-management:<verb>`) for environments that don't accept the
`::` separator.

## `…​.list`

```bash
php artisan laranail::package-management.list
```

Lists every discovered extension — id · role · version · state.

## `…​.discover`

```bash
php artisan laranail::package-management.discover
```

Rescans the configured `platform/*` paths and rebuilds the compiled manifest cache. Run it after adding or
removing an extension directory (activation state is applied fresh each request, so enable/disable never
needs it).

## `…​.enable` / `…​.disable`

```bash
php artisan laranail::package-management.enable Blog
php artisan laranail::package-management.disable Blog
```

Activate / deactivate an extension. `enable` validates dependencies (present + active + semver
`require` + `minimum_core_version`); `disable` is guarded by reverse-dependencies. Neither runs migrations
or publishes assets — use `install` for that.

## `…​.install`

```bash
php artisan laranail::package-management.install Blog
```

Activate **and** run the extension's own migrations, publish its assets, and seed its default settings.
Fires the `install` event pair + the `installed` hook.

## `…​.update`

```bash
php artisan laranail::package-management.update Blog
```

Run any pending migrations for an already-installed extension (fires the `update` event pair + hooks).

## `…​.remove`

```bash
php artisan laranail::package-management.remove Blog
```

Deactivate, unpublish assets, and forget the management state (activation flag, version, settings). The
extension's **own database tables are preserved** — removing an extension never destroys user data (opt in
to migration rollback with `installer.rollback_migrations_on_remove`).

## `…​.cache`

```bash
php artisan laranail::package-management.cache          # compile the discovered-extensions cache
php artisan laranail::package-management.cache --clear  # delete the compiled cache
```

The compiled cache (`config('laranail.package-management.cache')`) stores the *discovered* set only.

## `…​.install-from`

```bash
php artisan laranail::package-management.install-from acme/blog --ref=v1.2.0 [--as=module] [--token=…] [--force]
```

Download an extension from a VCS provider (GitHub / GitLab / Bitbucket), place it under the configured
`platform/{role}s/`, and run the full install — all behind a rollback stack. See
[Installer](installer.md).

---

[← Docs index](../../README.md#documentation)
