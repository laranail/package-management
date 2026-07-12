# Compile the manifest cache for production

The loader can compile the *discovered* extension set to a PHP file so boots skip the filesystem scan.
Activation state is applied fresh each request, so the cache only changes when an extension directory is
added or removed.

```php
// config/package-management.php
'cache' => [
    'enabled' => true,                                       // env PACKAGE_MANAGEMENT_CACHE=true
    'path'    => 'bootstrap/cache/laranail-extensions.php',
],
```

```bash
php artisan laranail::package-management.cache          # build the compiled cache
php artisan laranail::package-management.cache --clear  # drop it
php artisan laranail::package-management.discover       # rescan + rebuild after adding/removing an extension
```

See [Configuration](../configuration.md) for the full cache options.

---

[← Docs index](../../README.md#documentation)
