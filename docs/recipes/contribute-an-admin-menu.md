# Contribute an admin-menu entry

The loader carries menu entries but never renders them — the host collects and renders them. Declare
entries as manifest data, or compute them via a hook contract.

```json
// module.json / plugin.json — declarative
{ "menu": [ { "label": "Shop", "url": "/admin/shop", "icon": "cart", "group": "Commerce", "order": 10 } ] }
```

```php
// or computed: the extension's hook implements ContributesNavigation
use Simtabi\Laranail\Package\Management\Contracts\ContributesNavigation;
use Simtabi\Laranail\Package\Management\Extension;

final class ShopHook implements ContributesNavigation
{
    public function navigation(Extension $e): array
    {
        return [['label' => 'Shop', 'url' => '/admin/shop']];
    }
}
```

The host collects both by iterating the active set — see the pattern in
[Host integration](../tools/host-integration.md#admin-menu-contribution).

---

[← Docs index](../../README.md#documentation)
