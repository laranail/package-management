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

    // Register the extension's service provider(s) with the host framework.
    public function registerProviders(Extension $extension): void;
}
```

Migration running and asset publishing are **not** on this base interface — they are optional
capabilities an adapter may also implement: `Contracts\RunsMigrations`, `Contracts\PublishesAssets`,
`Contracts\RecordsInstall`, `Contracts\SeedsSettings`. The manager checks `instanceof` and skips the step
when unsupported, so an adapter degrades gracefully.

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
- **`SymfonyLoaderAdapter`** (ships): same runtime PSR-4; sets each provider as a service instance on a
  Symfony `ContainerInterface` (`$container->set($fqcn, new $provider())`). Symfony builds its container
  at compile time, so runtime registration is limited to service instances — a compiler pass / bundle is
  the build-time route for tags, autowiring and aliases. Providers must be no-arg constructible. Requires
  `symfony/dependency-injection` (a `suggest`). Bind it in your kernel/container setup:
  ```php
  use Simtabi\Laranail\Package\Management\Adapters\SymfonyLoaderAdapter;

  $adapter = new SymfonyLoaderAdapter($container);
  ```

## Adding a framework

1. Implement `LoaderAdapter` for the framework.
2. Bind it in the host (or auto-select from `config('laranail.package-management.adapter')`).
3. That's it — discovery, dependency resolution, activation store, cache, and the CLI are all
   framework-neutral and unchanged.

This mirrors the scaffolder's **flavors** (vanilla/laravel/lumen/symfony): the scaffolder *generates*
per framework, this loader *runs* per framework — the two axes line up.

[← Docs index](../README.md#documentation)
