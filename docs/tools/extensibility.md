# Extensibility

Runtime seams for host apps and other packages to extend the loader without forking it.

## 1. Macroable manager + fluent DSL

`ExtensionManager` (behind the `Extensions` facade) is `Macroable` — add methods at runtime:

```php
use Simtabi\Laranail\Package\Management\ExtensionManager;

ExtensionManager::macro('activeCount', function (): int {
    return count($this->active()); // $this is the manager
});

Extensions::activeCount();
```

The built-in DSL seam is `pipe()` (see §2).

## 2. Manifest pipeline

Every discovered `Extension` passes through ordered, pluggable stages (normalize / validate / enrich)
before it reaches the loader. A stage is any `handle(Extension $extension, Closure $next): Extension`.
Stages come from three sources, applied in this order:

1. **config** `laranail.package-management.pipeline.stages` — class-strings only (cache-safe);
2. **container tag** `laranail.manifest.stages`;
3. **runtime** `Extensions::pipe($classStringOrClosure)`.

```php
$this->app->tag([EnrichVersionStage::class], 'laranail.manifest.stages');

Extensions::pipe(fn (Extension $e, Closure $next) => $next($e)); // runtime
```

Stages run at **discovery**, so their output is what gets compiled into the manifest cache — rebuild the
cache (`…​.discover` / `…​.cache`) after adding a stage at runtime.

## 3. Caching decorator (container decoration)

Set `laranail.package-management.activation.cache = true` (database store) and the
`ExtensionStateRepositoryInterface` is wrapped in `CachingExtensionStateRepository` via the container's
`extend()` — the hot `activeNames()` read is cached, every write flushes it, and a
`FlushExtensionStateCache` listener also flushes on any lifecycle event (covering writes that bypass the
decorator). Swap in your own decorator the same way from a host provider.

## 4. Pluggable drivers via a Laravel Manager

The VCS installer's `SourceDriverManager extends Illuminate\Support\Manager`, so hosts register more
providers with the standard `extend()` (see [installer.md](installer.md)):

```php
$this->app->make(SourceDriverManager::class)->extend('gitea', fn () => new GiteaSourceDriver(...));
```

## 5. Lifecycle events (present + past tense)

Each transition dispatches a pre/post pair — subscribe to either:

| Transition | Before | After |
|---|---|---|
| activate | `ExtensionActivating` | `ExtensionActivated` |
| deactivate | `ExtensionDeactivating` | `ExtensionDeactivated` |
| install | `ExtensionInstalling` | `ExtensionInstalled` |
| update | `ExtensionUpdating` | `ExtensionUpdated` |
| remove | `ExtensionRemoving` | `ExtensionRemoved` |

Each extension may also ship a duck-typed `hook` class implementing any of
`activated`/`deactivated`/`installed`/`removed`/`updating`/`updated` (see [lifecycle.md](../lifecycle.md)).

## 6. Facade spy seam

`Extensions` is a real facade over the singleton manager, so it spies/fakes in consumer tests:

```php
Extensions::spy();
// … code under test calls Extensions::install('acme/blog') …
Extensions::shouldHaveReceived('install')->with('acme/blog');
```

[← Docs index](../../README.md#documentation)
