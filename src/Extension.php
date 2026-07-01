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
    ) {}

    /** PSR-4 source root registered on the runtime autoloader. */
    public function sourcePath(): string
    {
        return rtrim($this->path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'src';
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
            $this->require, $this->role, $this->path, $enabled,
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
        );
    }
}
