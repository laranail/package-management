# Install from a VCS repo

Install an extension straight from GitHub / GitLab / Bitbucket, placed into your `platform/` tree and run
through the full lifecycle behind a rollback stack.

```bash
php artisan laranail::package-management.install-from acme/blog --ref=v1.2.0
php artisan laranail::package-management.install-from https://gitlab.com/acme/blog --as=module --token=…
```

Configure the default provider + per-provider tokens under `installer.*` in `config/package-management.php`
(tokens are never logged). A failed install leaves no files, tables, or state behind.

See the [Installer reference](../tools/installer.md) for drivers, the rollback stack, and `--force`.

---

[← Docs index](../../README.md#documentation)
