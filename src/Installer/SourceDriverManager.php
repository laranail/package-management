<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Installer;

use Illuminate\Support\Manager;
use RuntimeException;
use Simtabi\Laranail\Package\Management\Contracts\SourceDriver;
use Simtabi\Laranail\Package\Management\Installer\Drivers\BitbucketSourceDriver;
use Simtabi\Laranail\Package\Management\Installer\Drivers\GithubSourceDriver;
use Simtabi\Laranail\Package\Management\Installer\Drivers\GitlabSourceDriver;

/**
 * Resolves a {@see SourceDriver} for a repository ref. Host apps register more providers
 * with the inherited `extend('provider', fn () => new MyDriver(...))`.
 */
final class SourceDriverManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return (string) $this->config->get('laranail.package-management.installer.default_provider', 'github');
    }

    public function forRef(RepositoryRef $ref): SourceDriver
    {
        $driver = $this->driver($ref->provider);

        if (! $driver instanceof SourceDriver) {
            throw new RuntimeException("Source driver [{$ref->provider}] must implement " . SourceDriver::class . '.');
        }

        return $driver;
    }

    protected function createGithubDriver(): SourceDriver
    {
        return new GithubSourceDriver($this->timeout(), $this->token('github'));
    }

    protected function createGitlabDriver(): SourceDriver
    {
        return new GitlabSourceDriver($this->timeout(), $this->token('gitlab'));
    }

    protected function createBitbucketDriver(): SourceDriver
    {
        return new BitbucketSourceDriver($this->timeout(), $this->token('bitbucket'));
    }

    private function timeout(): int
    {
        return (int) $this->config->get('laranail.package-management.installer.timeout', 60);
    }

    private function token(string $provider): ?string
    {
        $token = $this->config->get("laranail.package-management.installer.tokens.{$provider}");

        return $token === null ? null : (string) $token;
    }
}
