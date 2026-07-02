<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Listeners;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Simtabi\Laranail\Package\Management\Repositories\CachingExtensionStateRepository;

/**
 * Flushes the cached active-set on any lifecycle event — so writes that bypass the
 * caching decorator (e.g. direct model writes) still invalidate the cache.
 */
final readonly class FlushExtensionStateCache
{
    public function __construct(private CacheRepository $cache) {}

    public function handle(): void
    {
        $this->cache->forget(CachingExtensionStateRepository::CACHE_KEY);
    }
}
