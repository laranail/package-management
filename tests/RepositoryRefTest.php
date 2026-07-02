<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Simtabi\Laranail\Package\Management\Installer\RepositoryRef;

class RepositoryRefTest extends BaseTestCase
{
    public function test_parses_a_bare_owner_repo_with_the_default_provider(): void
    {
        $ref = RepositoryRef::parse('acme/blog');

        $this->assertSame('github', $ref->provider);
        $this->assertSame('acme', $ref->owner);
        $this->assertSame('blog', $ref->repo);
        $this->assertSame('HEAD', $ref->ref);
        $this->assertNull($ref->token);
    }

    public function test_parses_full_urls_and_detects_the_provider(): void
    {
        $github = RepositoryRef::parse('https://github.com/acme/blog.git', 'v1.2.0');
        $this->assertSame('github', $github->provider);
        $this->assertSame('blog', $github->repo);
        $this->assertSame('v1.2.0', $github->ref);

        $this->assertSame('gitlab', RepositoryRef::parse('gitlab.com/acme/blog')->provider);
        $this->assertSame('bitbucket', RepositoryRef::parse('https://bitbucket.org/acme/blog')->provider);

        // scp-like git@host:owner/repo.git
        $ssh = RepositoryRef::parse('git@github.com:acme/blog.git');
        $this->assertSame('github', $ssh->provider);
        $this->assertSame('acme', $ssh->owner);
        $this->assertSame('blog', $ssh->repo);
    }

    public function test_carries_the_token(): void
    {
        $this->assertSame('tok', RepositoryRef::parse('acme/blog', null, 'tok')->token);
    }

    public function test_rejects_input_without_owner_and_repo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        RepositoryRef::parse('not-a-repo');
    }
}
