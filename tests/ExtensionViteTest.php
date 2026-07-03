<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\HtmlString;

class ExtensionViteTest extends TestCase
{
    private string $publicDir;

    protected function setUp(): void
    {
        $this->publicDir = sys_get_temp_dir() . '/laranail-pm-vite-' . getmypid() . '-' . uniqid();

        // an extension's published Vite build: public/vendor/{slug}/build/manifest.json
        $build = $this->publicDir . '/vendor/acme-blog/build';
        @mkdir($build, 0777, true);
        file_put_contents($build . '/manifest.json', (string) json_encode([
            'resources/js/app.js' => ['file' => 'assets/app-abc123.js', 'src' => 'resources/js/app.js', 'isEntry' => true],
        ]));

        parent::setUp();
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->publicDir);
        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app->usePublicPath($this->publicDir);
        $app['config']->set('app.url', 'http://localhost');
    }

    public function test_it_renders_tags_from_the_extensions_published_build_dir(): void
    {
        $html = extension_vite('acme/blog', 'resources/js/app.js');

        $this->assertInstanceOf(HtmlString::class, $html);
        $rendered = (string) $html;

        $this->assertStringContainsString('vendor/acme-blog/build/assets/app-abc123.js', $rendered);
        $this->assertStringContainsString('<script', $rendered);
    }

    public function test_it_accepts_a_custom_build_directory(): void
    {
        $custom = $this->publicDir . '/custom/build';
        @mkdir($custom, 0777, true);
        file_put_contents($custom . '/manifest.json', (string) json_encode([
            'resources/js/app.js' => ['file' => 'assets/app-xyz789.js', 'src' => 'resources/js/app.js', 'isEntry' => true],
        ]));

        $rendered = (string) extension_vite('acme/blog', 'resources/js/app.js', 'custom/build');

        $this->assertStringContainsString('custom/build/assets/app-xyz789.js', $rendered);
    }
}
