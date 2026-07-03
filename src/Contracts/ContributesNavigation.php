<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Contracts;

use Simtabi\Laranail\Package\Management\Extension;

/**
 * Optional, type-safe way for an extension's `hook` class to contribute host-navigation
 * entries. The **host** collects these — it iterates the active extensions
 * (`Extensions::query()->active()`), resolves each `hook`, and calls `navigation()` when
 * the hook implements this. The loader itself never renders, stores, or consumes menus,
 * and no lifecycle path touches it — it stays a lean, framework-agnostic registrar.
 *
 * A purely declarative alternative is the manifest `menu` array (surfaced on
 * {@see Extension::$menu}); implement this contract only when entries must be computed.
 */
interface ContributesNavigation
{
    /**
     * @return list<array{label: string, url: string, icon?: string, group?: string, order?: int}>
     */
    public function navigation(Extension $extension): array;
}
