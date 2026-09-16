<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

use ReflectionClass;
use InvalidArgumentException;
use Simtabi\Laranail\Package\Management\Installer\RepositoryRef;
use Simtabi\Laranail\Package\Management\Installer\SourceDriverManager;

/**
 * Asserts that the four provider lists this package keeps in three different files still agree.
 *
 * `SourceDriverManager extends Illuminate\Support\Manager`, which resolves by **interpolating the
 * provider name into a method name** — `driver('gitlab')` becomes `createGitlabDriver()`. Nothing
 * type-checks that interpolation, and the name arrives from three independent places:
 *
 *  1. `RepositoryRef::HOSTS` maps a hostname to a provider, so pasting a GitLab URL produces
 *     `gitlab` whether or not a driver exists.
 *  2. `installer.default_provider` is env-driven (`PACKAGE_MANAGEMENT_VCS`), so a deployment can
 *     name a provider that was never built.
 *  3. `installer.tokens.*` keys are read back as `tokens.{$provider}`.
 *
 * The two failure modes differ, and the quieter one is worse. A missing driver is an
 * `InvalidArgumentException` mid-install. A missing **token key** is not an error at all:
 * `token()` returns null, the driver requests a private repository unauthenticated, and the host
 * answers 404 — which reads as a repository that does not exist rather than a credential that was
 * never sent.
 *
 * No network: these assertions are about which methods and keys exist, not about any repository.
 */
final class SourceDriverContractTest extends TestCase
{
    public function test_every_provider_the_url_parser_can_produce_has_a_driver(): void
    {
        foreach ($this->parsedProviders() as $provider) {
            $this->assertTrue(
                method_exists(SourceDriverManager::class, $this->createMethodFor($provider)),
                "a repository URL parses to the provider [{$provider}], which SourceDriverManager "
                . 'cannot build: ' . $this->createMethodFor($provider) . '() does not exist',
            );
        }
    }

    public function test_the_shipped_default_provider_has_a_driver(): void
    {
        // The one name least likely to be exercised before a real install, and most likely to be
        // overridden per-deployment through PACKAGE_MANAGEMENT_VCS.
        $default = $this->shippedInstallerConfig()['default_provider'];

        $this->assertIsString($default);
        $this->assertTrue(
            method_exists(SourceDriverManager::class, $this->createMethodFor($default)),
            "the shipped default provider [{$default}] has no driver",
        );
    }

    public function test_every_configurable_token_belongs_to_a_real_driver(): void
    {
        // A token key naming a provider that cannot be built is a credential a consumer sets and
        // that nothing ever reads.
        foreach (array_keys($this->shippedInstallerConfig()['tokens']) as $provider) {
            $this->assertTrue(
                method_exists(SourceDriverManager::class, $this->createMethodFor((string) $provider)),
                "config offers a token for [{$provider}], which has no driver",
            );
        }
    }

    public function test_every_driver_can_be_given_a_token(): void
    {
        // The silent direction: token() reads tokens.{provider} and returns null when the key is
        // absent, so a driver with no key requests private repositories unauthenticated and gets a
        // 404 that looks like a missing repository.
        $tokens = array_keys($this->shippedInstallerConfig()['tokens']);

        foreach ($this->buildableProviders() as $provider) {
            $this->assertContains(
                $provider,
                $tokens,
                "the [{$provider}] driver exists but config offers no installer.tokens.{$provider}, "
                . 'so it can only ever reach public repositories',
            );
        }
    }

    public function test_an_unknown_provider_fails_loudly(): void
    {
        // Pinning the failure mode: an exception, not a null driver that fails later as a confusing
        // download error.
        $this->expectException(InvalidArgumentException::class);

        app(SourceDriverManager::class)->forRef(
            new RepositoryRef('not-a-real-provider', 'acme', 'widget'),
        );
    }

    private function createMethodFor(string $provider): string
    {
        // Manager studlies the name: 'azure-devops' would become createAzureDevopsDriver.
        return 'create' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $provider))) . 'Driver';
    }

    /** The provider names the URL parser can actually produce, read from its own host map. */
    private function parsedProviders(): array
    {
        $hosts = (new ReflectionClass(RepositoryRef::class))->getConstant('HOSTS');

        $this->assertNotEmpty($hosts, 'RepositoryRef::HOSTS is empty, so this guard would be vacuous');

        // Driven through the real parser rather than trusting the map: this covers the mapping and
        // the host-stripping together, which is what a consumer's pasted URL actually exercises.
        return array_map(
            static fn (string $host): string => RepositoryRef::parse("https://{$host}/acme/widget")->provider,
            array_keys($hosts),
        );
    }

    private function shippedInstallerConfig(): array
    {
        return (require __DIR__ . '/../config/package-management.php')['installer'];
    }

    /** The provider names the manager can build, read back off the class. */
    private function buildableProviders(): array
    {
        $providers = [];

        foreach ((new ReflectionClass(SourceDriverManager::class))->getMethods() as $method) {
            if (preg_match('/^create(.+)Driver$/', $method->getName(), $m) === 1) {
                $providers[] = strtolower($m[1]);
            }
        }

        return $providers;
    }
}
