# Add a framework adapter

Support a new framework by implementing `LoaderAdapter` and binding it — discovery, dependency resolution,
the activation store, the cache, and the CLI are all framework-neutral and unchanged.

```php
use Simtabi\Laranail\Package\Management\Contracts\LoaderAdapter;
use Simtabi\Laranail\Package\Management\Adapters\Concerns\RegistersRuntimeAutoload;
use Simtabi\Laranail\Package\Management\Extension;

final class MyFrameworkAdapter implements LoaderAdapter
{
    use RegistersRuntimeAutoload; // shared runtime PSR-4

    public function registerProviders(Extension $extension): void
    {
        foreach ($extension->providers as $provider) {
            if (class_exists($provider)) { /* register with your framework */ }
        }
    }
}
```

```php
// a host service provider
$this->app->bind(LoaderAdapter::class, MyFrameworkAdapter::class);
```

Opt into migrations/asset publishing by also implementing `Contracts\RunsMigrations` / `PublishesAssets`
(the manager feature-detects them). See the [Adapters reference](../tools/adapters.md).

---

[← Docs index](../../README.md#documentation)
