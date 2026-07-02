<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management;

/**
 * Immutable description of one discovered extension, normalized from a manifest
 * (composer.json / module.json / plugin.json). See docs/manifests.md.
 */
final readonly class Extension
{
    /**
     * @param  list<string>  $providers  service-provider FQCNs
     * @param  list<string>  $require  extension ids that must load first
     * @param  array<string, mixed>  $defaultSettings  manifest default settings, seeded on install
     * @param  array<string, string>  $requireVersions  dep id => semver constraint (map require form)
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $namespace,
        public array $providers,
        public string $version,
        public array $require,
        public string $role,     // package | module | plugin
        public string $path,
        public bool $enabled = false,
        public ?string $hook = null, // optional LifecycleHook FQCN
        public array $defaultSettings = [],
        public int $priority = 0,             // load-order hint (dependencies still win)
        public string $type = '',             // plugin | nova | filament (panel plugins)
        public ?string $minimumCoreVersion = null, // required package-management version
        public array $requireVersions = [],   // dep id => semver constraint (from the map require form)
    ) {}

    /** PSR-4 source root registered on the runtime autoloader. */
    public function sourcePath(): string
    {
        return rtrim($this->path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'src';
    }

    /** Filesystem-safe id (e.g. `acme/blog` → `acme-blog`), used for published-asset paths. */
    public function slug(): string
    {
        return str_replace('/', '-', $this->id);
    }

    /** Packages are Composer-autoloaded already; only modules/plugins are runtime-loaded. */
    public function isRuntimeLoaded(): bool
    {
        return $this->role !== 'package';
    }

    public function withEnabled(bool $enabled): self
    {
        return new self(
            $this->id, $this->name, $this->namespace, $this->providers, $this->version,
            $this->require, $this->role, $this->path, $enabled, $this->hook, $this->defaultSettings,
            $this->priority, $this->type, $this->minimumCoreVersion, $this->requireVersions,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'namespace' => $this->namespace,
            'providers' => $this->providers,
            'version' => $this->version,
            'require' => $this->require,
            'role' => $this->role,
            'path' => $this->path,
            'enabled' => $this->enabled,
            'hook' => $this->hook,
            'defaultSettings' => $this->defaultSettings,
            'priority' => $this->priority,
            'type' => $this->type,
            'minimumCoreVersion' => $this->minimumCoreVersion,
            'requireVersions' => $this->requireVersions,
        ];
    }

    /**
     * Rehydrate from a {@see toArray()} payload — used by the compiled manifest cache.
     * The `enabled` flag is intentionally not restored; activation is applied fresh
     * from the ActivationStore on every request.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            namespace: (string) ($data['namespace'] ?? ''),
            providers: array_values(array_map(strval(...), (array) ($data['providers'] ?? []))),
            version: (string) ($data['version'] ?? '0.0.0'),
            require: array_values(array_map(strval(...), (array) ($data['require'] ?? []))),
            role: (string) ($data['role'] ?? 'package'),
            path: (string) ($data['path'] ?? ''),
            hook: isset($data['hook']) ? (string) $data['hook'] : null,
            defaultSettings: (array) ($data['defaultSettings'] ?? []),
            priority: (int) ($data['priority'] ?? 0),
            type: (string) ($data['type'] ?? ''),
            minimumCoreVersion: isset($data['minimumCoreVersion']) ? (string) $data['minimumCoreVersion'] : null,
            requireVersions: array_map(strval(...), (array) ($data['requireVersions'] ?? [])),
        );
    }
}
