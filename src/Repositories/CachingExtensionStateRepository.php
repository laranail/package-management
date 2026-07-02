<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Repositories;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Simtabi\Laranail\Package\Management\Contracts\ExtensionStateRepositoryInterface;
use Simtabi\Laranail\Package\Management\Models\ExtensionState;

/**
 * Caching decorator over an {@see ExtensionStateRepositoryInterface} — the hot
 * `activeNames()` read is cached; every write flushes it. Wired via the container's
 * `extend()` in the service provider when `activation.cache` is on.
 */
final readonly class CachingExtensionStateRepository implements ExtensionStateRepositoryInterface
{
    public const string CACHE_KEY = 'laranail:pm:state:active';

    public function __construct(
        private ExtensionStateRepositoryInterface $inner,
        private CacheRepository $cache,
    ) {}

    /** @return list<string> */
    public function activeNames(): array
    {
        /** @var list<string> $names */
        $names = $this->cache->remember(self::CACHE_KEY, 60, fn (): array => $this->inner->activeNames());

        return $names;
    }

    public function isActive(string $name): bool
    {
        return in_array($name, $this->activeNames(), true);
    }

    public function find(string $name): ?ExtensionState
    {
        return $this->inner->find($name);
    }

    public function markActive(string $name): ExtensionState
    {
        $this->flush();

        return $this->inner->markActive($name);
    }

    public function markInactive(string $name): void
    {
        $this->flush();
        $this->inner->markInactive($name);
    }

    public function forget(string $name): void
    {
        $this->flush();
        $this->inner->forget($name);
    }

    public function recordInstall(string $name, ?string $version): ExtensionState
    {
        $this->flush();

        return $this->inner->recordInstall($name, $version);
    }

    /** @return array<string, mixed> */
    public function settings(string $name): array
    {
        return $this->inner->settings($name);
    }

    /** @param  array<string, mixed>  $settings */
    public function putSettings(string $name, array $settings): void
    {
        $this->inner->putSettings($name, $settings);
    }

    /** @param  array<string, mixed>  $defaults */
    public function seedSettings(string $name, array $defaults): void
    {
        $this->inner->seedSettings($name, $defaults);
    }

    public function flush(): void
    {
        $this->cache->forget(self::CACHE_KEY);
    }
}
