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

- **`LaravelLoaderAdapter`** (ships): runtime PSR-4 (shared trait) + `$app->register($provider)`, so
  Laravel handles deferred providers, boot ordering and publishing.
- **`LumenLoaderAdapter`** (ships): same runtime PSR-4, but registers against the bare container —
  Lumen's `Application::register()` when present, otherwise it instantiates the provider and calls
  `register()`/`boot()` itself (booting through the container for DI). Lumen has no package
  auto-discovery, so bind it explicitly:
  ```php
  use Simtabi\Laranail\Package\Management\Adapters\LumenLoaderAdapter;
  use Simtabi\Laranail\Package\Management\Contracts\LoaderAdapter;

  $app->singleton(LoaderAdapter::class, fn ($app) => new LumenLoaderAdapter($app));
  ```
- **`SymfonyLoaderAdapter`** (future): maps providers to Symfony bundles / DI extensions.

## Adding a framework

1. Implement `LoaderAdapter` for the framework.
2. Bind it in the host (or auto-select from `config('package-management.adapter')`).
3. That's it — discovery, dependency resolution, activation store, cache, and the CLI are all
   framework-neutral and unchanged.

This mirrors the scaffolder's **flavors** (vanilla/laravel/lumen/symfony): the scaffolder *generates*
per framework, this loader *runs* per framework — the two axes line up.

[← Docs index](../README.md#documentation)
