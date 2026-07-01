<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Manifests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Simtabi\Laranail\Package\Management\Extension;

/**
 * Reads + validates composer.json / module.json / plugin.json and normalizes each
 * into an Extension. A malformed manifest yields null (skipped, never fatal). The
 * schemas are documented in docs/manifests.md — the contract with the scaffolder.
 */
final readonly class ManifestReader
{
    public function __construct(private Filesystem $files) {}

    public function read(string $dir, string $role): ?Extension
    {
        return match ($role) {
            'module' => $this->readModule($dir),
            'plugin' => $this->readPlugin($dir),
            'package' => $this->readPackage($dir),
            default => null,
        };
    }

    /** @return array<string, mixed>|null */
    private function json(string $file): ?array
    {
        if (! $this->files->isFile($file)) {
            return null;
        }

        $data = json_decode((string) $this->files->get($file), true);

        return is_array($data) ? $data : null;
    }

    private function readModule(string $dir): ?Extension
    {
        $m = $this->json($dir . '/module.json');
        if ($m === null || empty($m['name'])) {
            return null;
        }

        $providers = array_values(array_filter((array) ($m['providers'] ?? [])));

        return new Extension(
            id: (string) ($m['alias'] ?? Str::kebab((string) $m['name'])),
            name: (string) $m['name'],
            namespace: $this->namespaceFromProvider($providers[0] ?? ''),
            providers: $providers,
            version: (string) ($m['version'] ?? '0.0.0'),
            require: array_values((array) ($m['require'] ?? [])),
            role: 'module',
            path: $dir,
            hook: isset($m['hook']) ? (string) $m['hook'] : null,
        );
    }

    private function readPlugin(string $dir): ?Extension
    {
        $p = $this->json($dir . '/plugin.json');
        if ($p === null || empty($p['name']) || empty($p['provider'])) {
            return null;
        }

        $namespace = (string) ($p['namespace'] ?? $this->namespaceFromProvider((string) $p['provider']));

        return new Extension(
            id: (string) ($p['id'] ?? Str::kebab((string) $p['name'])),
            name: (string) $p['name'],
            namespace: rtrim($namespace, '\\') . '\\',
            providers: [(string) $p['provider']],
            version: (string) ($p['version'] ?? '0.0.0'),
            require: array_values((array) ($p['require'] ?? [])),
            role: 'plugin',
            path: $dir,
            hook: isset($p['hook']) ? (string) $p['hook'] : null,
        );
    }

    private function readPackage(string $dir): ?Extension
    {
        $c = $this->json($dir . '/composer.json');
        if ($c === null || empty($c['name'])) {
            return null;
        }

        $psr4 = (array) ($c['autoload']['psr-4'] ?? []);

        return new Extension(
            id: (string) $c['name'],
            name: Str::studly(Str::afterLast((string) $c['name'], '/')),
            namespace: (string) (array_key_first($psr4) ?? ''),
            providers: array_values((array) ($c['extra']['laravel']['providers'] ?? [])),
            version: (string) ($c['version'] ?? '0.0.0'),
            require: [],
            role: 'package',
            path: $dir,
        );
    }

    /** `Vendor\Pkg\Providers\PkgServiceProvider` → `Vendor\Pkg\` (drop Providers\Xxx). */
    private function namespaceFromProvider(string $provider): string
    {
        $parts = array_values(array_filter(explode('\\', trim($provider, '\\'))));

        if (count($parts) > 2) {
            $parts = array_slice($parts, 0, -2);
        }

        return $parts === [] ? '' : implode('\\', $parts) . '\\';
    }
}
