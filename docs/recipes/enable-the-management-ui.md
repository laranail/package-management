# Enable the management UI

package-management ships an **opt-in** web UI to list extensions and drive the lifecycle
(enable / disable / install / update / remove + install-from-VCS). It's off by default — protect it with
your own auth middleware.

```php
// config/package-management.php
'ui' => [
    'enabled'    => true,                       // env PACKAGE_MANAGEMENT_UI=true
    'prefix'     => 'laranail/extensions',      // the route prefix
    'middleware' => ['web', 'auth', 'can:manage-extensions'],
],
```

When enabled the provider registers, under `{prefix}`:

- `GET  /` — the extension list (Blade view, publishable / overridable via the `package-management` view namespace)
- `POST /{enable,disable,install,update,remove}` and `POST /install-from`
  (named `laranail.extensions.*`)

The UI is a thin front-end over the same `ExtensionManager` the CLI uses — the guards, events, and hooks
all apply. See [Configuration](../configuration.md) for the `ui.*` keys.

---

[← Docs index](../../README.md#documentation)
