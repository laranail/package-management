# Write a lifecycle hook

Run code when an extension is activated / installed / updated / removed. Name the hook FQCN in the
manifest; the loader resolves it from the container and calls whichever methods exist — **duck-typed**, so
the extension needs no dependency on this package.

```json
// module.json / plugin.json
{ "hook": "Acme\\Blog\\Hooks\\BlogHook" }
```

```php
namespace Acme\Blog\Hooks;

final class BlogHook // no import, no interface required
{
    public function activated(object $extension): void { /* warm caches */ }
    public function installed(object $extension): void { /* seed reference data */ }
    public function updating(object $extension): void  { /* pre-migration work */ }
    public function removed(object $extension): void   { /* clean up */ }
}
```

If you already depend on the loader, implement `Contracts\LifecycleHook` / `InstallHook` for type-safety.
A missing method (or class) is simply skipped — never fatal. See [Lifecycle](../lifecycle.md).

---

[← Docs index](../../README.md#documentation)
