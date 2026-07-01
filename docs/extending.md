# Extending — framework adapters

The loader is framework-agnostic at its core (manifest scan → dependency sort → Composer `ClassLoader`
PSR-4 → provider registration → compiled cache). Everything framework-specific lives behind a
**`LoaderAdapter`**, so supporting a new framework is an adapter, not a rewrite.

## The `LoaderAdapter` interface

```php
interface LoaderAdapter
{
    // Register an extension's PSR-4 namespace at runtime (Composer ClassLoader).
    public function registerAutoload(Extension $extension): void;

    // Register the extension's provider/bootstrap with the host framework.
    public function registerProvider(Extension $extension): void;

    // Publish/boot backend + frontend resources (routes, migrations, views, assets).
    public function boot(Extension $extension): void;
}
```

- **`LaravelLoaderAdapter`** (ships): `ClassLoader::setPsr4()->register()` + `$app->register($provider)`
  + Laravel publishing/migrations.
- **`LumenLoaderAdapter`** (planned): registers via the Lumen application bootstrap; no Blade/facade
  auto-registration.
- **`SymfonyLoaderAdapter`** (future): maps providers to Symfony bundles / DI extensions.

## Adding a framework

1. Implement `LoaderAdapter` for the framework.
2. Bind it in the host (or auto-select from `config('package-management.adapter')`).
3. That's it — discovery, dependency resolution, activation store, cache, and the CLI are all
   framework-neutral and unchanged.

This mirrors the scaffolder's **flavors** (vanilla/laravel/lumen/symfony): the scaffolder *generates*
per framework, this loader *runs* per framework — the two axes line up.

[← Docs index](../README.md#documentation)
