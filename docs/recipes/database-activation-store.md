# Use the database activation store

Switch activation state from the default JSON file to the Eloquent-backed store (rich state: version,
settings, timestamps).

```php
// config/package-management.php
'activation' => [
    'store'      => 'database',                 // or env PACKAGE_MANAGEMENT_STORE=database
    'table'      => 'laranail_extension_states',// configurable
    'connection' => null,                        // null = the app's default connection
    'cache'      => true,                         // wrap the state repo in a caching decorator
],
```

```bash
php artisan migrate   # creates the state table
```

Reads degrade to "nothing active" until the table exists, so enabling the store on a fresh app never fatals.
See the [`ExtensionState` facade](../tools/facade.md) for the raw state API.

---

[← Docs index](../../README.md#documentation)
