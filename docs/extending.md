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
- **`SymfonyLoaderAdapter`** (ships): same runtime PSR-4. Requires `symfony/dependency-injection` (a
  `suggest`). Bind it in your kernel/container setup:
  ```php
  use Simtabi\Laranail\Package\Management\Adapters\SymfonyLoaderAdapter;

  $adapter = new SymfonyLoaderAdapter($container);
  ```

  See the Symfony section below for exactly what it does + the compile-time escape hatch.

## Symfony support (runtime service registration)

Symfony compiles its DI container at **build time** (bundles + compiler passes → a frozen, dumped
container), while this loader activates extensions at **runtime**. So Symfony support is scoped to
**runtime service registration** — the surface `Container::set()` allows. For each declared provider the
adapter:

- **instantiates** it, injecting the container when the constructor takes an argument (else no-arg —
  backward compatible);
- **`set()`s** the instance under its FQCN **and** under every interface it implements, so consumers can
  resolve by a stable interface;
- calls an optional **`register()` / `boot()`** (duck-typed; the container is passed when the method
  accepts it) — a runtime lifecycle seam mirroring the Lumen adapter.

If the container is already **compiled** (`ContainerBuilder::isCompiled()`), registration is a **no-op**
(not a fatal) — a stale manifest never breaks boot. Tags, autowiring, aliases, decoration and lazy
services are **not** available at runtime by design.

### Compile-time wiring (escape hatch)

When you genuinely need full Symfony DI for loaded extensions, register a **CompilerPass** in your kernel
that reads the loader's manifests and adds `Definition`s during compilation. Note the trade-off: compiler
passes run at build time, so the set of active extensions must be known at `cache:warmup`, and
enabling/disabling then requires a container rebuild — you give up this loader's runtime activation for
those services.

```php
use Simtabi\Laranail\Package\Management\ExtensionManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ExtensionServicesPass implements CompilerPassInterface
{
    public function __construct(private readonly ExtensionManager $extensions) {}

    public function process(ContainerBuilder $container): void
    {
        foreach ($this->extensions->query()->active()->get() as $extension) {
            foreach ($extension->providers as $provider) {
                if (! class_exists($provider)) {
                    continue;
                }
                $container->register($provider, $provider)
                    ->setAutowired(true)
                    ->setPublic(true);
                // ->addTag(...) as needed
            }
        }
    }
}

// in your Kernel::build():
$container->addCompilerPass(new ExtensionServicesPass($extensionManager));
```

A full Symfony **Bundle** (with a DI `Extension`, `services.php`, and a generated bundle artifact shape) is
a distinct build-time product — deliberately out of scope here; it would replace runtime activation
entirely.

## Adding a framework

1. Implement `LoaderAdapter` for the framework.
2. Bind it in the host (or auto-select from `config('laranail.package-management.adapter')`).
3. That's it — discovery, dependency resolution, activation store, cache, and the CLI are all
   framework-neutral and unchanged.

This mirrors the scaffolder's **flavors** (vanilla/laravel/lumen/symfony): the scaffolder *generates*
per framework, this loader *runs* per framework — the two axes line up.

[← Docs index](../README.md#documentation)
