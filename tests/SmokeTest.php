<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

class SmokeTest extends TestCase
{
    public function test_the_provider_boots_and_merges_config(): void
    {
        $this->assertIsArray(config('laranail.package-management.paths'));
        $this->assertArrayHasKey('modules', config('laranail.package-management.paths'));
        $this->assertArrayHasKey('packages', config('laranail.package-management.paths'));
        $this->assertArrayHasKey('plugins', config('laranail.package-management.paths'));
    }

    public function test_about_command_renders_the_package_section(): void
    {
        // validates the package-tools hasAboutSection callable (resolves the manager, counts extensions)
        $this->artisan('about')->assertSuccessful();
    }

    public function test_extension_path_helper_resolves_by_role(): void
    {
        $this->assertStringEndsWith(
            'platform/modules/Blog',
            str_replace(DIRECTORY_SEPARATOR, '/', extension_path('module', 'Blog')),
        );
        $this->assertStringEndsWith(
            'platform/plugins/Shop/src',
            str_replace(DIRECTORY_SEPARATOR, '/', extension_path('plugin', 'Shop', 'src')),
        );
    }
}
