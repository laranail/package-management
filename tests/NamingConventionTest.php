<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;
use Simtabi\Laranail\Package\Tools\Testing\NamingScope;
use Simtabi\Laranail\Package\Tools\Testing\AssertsRegisteredNames;

/**
 * Every Artisan command and view namespace the package registers carries the vendor and the
 * package slug, read from the live registries of the booted application.
 *
 * The `package-management:<verb>` aliases are the deprecated bare names. They stay registered
 * so existing scripts keep working, and each one says so when used.
 */
final class NamingConventionTest extends TestCase
{
    use AssertsRegisteredNames;

    private const array DEPRECATED_ALIASES = [
        'package-management:list'         => 'laranail::package-management.list',
        'package-management:enable'       => 'laranail::package-management.enable',
        'package-management:disable'      => 'laranail::package-management.disable',
        'package-management:discover'     => 'laranail::package-management.discover',
        'package-management:cache'        => 'laranail::package-management.cache',
        'package-management:install'      => 'laranail::package-management.install',
        'package-management:remove'       => 'laranail::package-management.remove',
        'package-management:update'       => 'laranail::package-management.update',
        'package-management:install-from' => 'laranail::package-management.install-from',
    ];

    public function test_the_commands_are_scoped_apart_from_the_deprecated_aliases(): void
    {
        $names = $this->assertCommandNamesScoped(
            $this->scope('src'),
            deprecated: array_keys(self::DEPRECATED_ALIASES),
            atLeast: 9,
        );

        foreach (self::DEPRECATED_ALIASES as $canonical) {
            self::assertContains($canonical, $names);
        }
    }

    public function test_every_deprecated_alias_resolves_to_its_canonical_command(): void
    {
        $all = Artisan::all();

        foreach (self::DEPRECATED_ALIASES as $alias => $canonical) {
            self::assertArrayHasKey($alias, $all);
            self::assertSame($canonical, $all[$alias]->getName());
            self::assertContains($alias, $all[$canonical]->deprecatedCommandAliases());
        }
    }

    public function test_invoking_a_deprecated_alias_names_the_replacement(): void
    {
        $output = new BufferedOutput;
        $exit = Artisan::call('package-management:list', [], $output);
        $text = $output->fetch();

        self::assertSame(0, $exit);
        self::assertStringContainsString('[package-management:list] is a deprecated alias', $text);
        self::assertStringContainsString('[laranail::package-management.list]', $text);
    }

    public function test_invoking_the_canonical_name_does_not_warn(): void
    {
        $output = new BufferedOutput;
        Artisan::call('laranail::package-management.list', [], $output);

        self::assertStringNotContainsString('deprecated alias', $output->fetch());
    }

    public function test_the_view_namespace_is_registered_in_both_spellings(): void
    {
        $names = $this->assertViewNamespacesScoped($this->scope('resources'), atLeast: 2);

        self::assertContains('laranail/package-management', $names);
        self::assertContains('laranail-package-management', $names);
        self::assertTrue(view()->exists('laranail/package-management::extensions.index'));
        self::assertTrue(view()->exists('laranail-package-management::extensions.index'));
    }

    /**
     * Scoped to a directory inside the package rather than its root, which also holds this
     * suite's vendor/: under the root, every framework registration reads as this package's
     * own (package-tools v0.1.3 NamingScope default).
     */
    private function scope(string $directory): NamingScope
    {
        return NamingScope::for(
            package: 'laranail/package-management',
            ownerNamespace: 'Simtabi\\Laranail\\Package\\Management\\',
            basePath: __DIR__ . '/../' . $directory,
        );
    }
}
