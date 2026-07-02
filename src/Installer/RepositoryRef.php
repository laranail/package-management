<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Installer;

use InvalidArgumentException;

/**
 * Immutable reference to a VCS repository + ref, parsed from user input. Accepts
 * `owner/repo`, `github.com/owner/repo`, `https://github.com/owner/repo(.git)`, and the
 * GitLab / Bitbucket equivalents.
 */
final readonly class RepositoryRef
{
    private const array HOSTS = [
        'github.com' => 'github',
        'gitlab.com' => 'gitlab',
        'bitbucket.org' => 'bitbucket',
    ];

    public function __construct(
        public string $provider,
        public string $owner,
        public string $repo,
        public string $ref = 'HEAD',
        public ?string $token = null,
    ) {}

    public static function parse(string $url, ?string $ref = null, ?string $token = null, string $default = 'github'): self
    {
        $raw = trim($url);
        $provider = $default;

        // strip scheme + user@, and detect the provider from a known host
        $raw = (string) preg_replace('#^[a-z]+://#i', '', $raw);
        $raw = (string) preg_replace('#^[^/@]+@#', '', $raw); // git@host: form
        $raw = str_replace(':', '/', $raw);                   // git@host:owner/repo → host/owner/repo

        foreach (self::HOSTS as $host => $name) {
            if (str_starts_with($raw, $host . '/')) {
                $provider = $name;
                $raw = substr($raw, strlen($host) + 1);
                break;
            }
        }

        $raw = preg_replace('#\.git$#', '', $raw) ?? $raw;
        $segments = array_values(array_filter(explode('/', $raw), static fn (string $s): bool => $s !== ''));

        if (count($segments) < 2) {
            throw new InvalidArgumentException("Cannot parse a repository from [{$url}] — expected owner/repo.");
        }

        return new self($provider, $segments[0], $segments[1], $ref ?? 'HEAD', $token);
    }
}
