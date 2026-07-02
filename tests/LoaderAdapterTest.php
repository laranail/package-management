<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Simtabi\Laranail\Package\Management\Adapters\LumenLoaderAdapter;
use Simtabi\Laranail\Package\Management\Adapters\SymfonyLoaderAdapter;
use Simtabi\Laranail\Package\Management\Extension;
use Simtabi\Laranail\Package\Management\Tests\Fixtures\FakeProvider;
use Simtabi\Laranail\Package\Management\Tests\Fixtures\FakeSymfonyService;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class LoaderAdapterTest extends BaseTestCase
{
    /** @param  list<string>  $providers */
    private function extension(array $providers): Extension
    {
        return new Extension('demo', 'Demo', 'Demo\\', $providers, '1.0.0', [], 'plugin', '/tmp/demo', true);
    }

    public function test_registers_providers_manually_on_a_bare_container(): void
    {
        $container = new Container;

        (new LumenLoaderAdapter($container))->registerProviders($this->extension([FakeProvider::class]));

        // both register() and boot() ran against the container
        $this->assertTrue($container->bound('fake.registered'));
        $this->assertTrue($container->bound('fake.booted'));
    }

    public function test_prefers_a_register_method_when_the_app_exposes_one(): void
    {
        $app = new class extends Container
        {
            /** @var list<string> */
            public array $registered = [];

            public function register(string $provider): void
            {
                $this->registered[] = $provider;
            }
        };

        (new LumenLoaderAdapter($app))->registerProviders($this->extension([FakeProvider::class]));

        $this->assertSame([FakeProvider::class], $app->registered);
        // register() short-circuits the manual path
        $this->assertFalse($app->bound('fake.registered'));
    }

    public function test_missing_provider_classes_are_skipped(): void
    {
        $container = new Container;

        (new LumenLoaderAdapter($container))->registerProviders($this->extension(['No\\Such\\Provider']));

        $this->assertFalse($container->bound('fake.registered'));
    }

    public function test_symfony_adapter_sets_providers_as_container_services(): void
    {
        $container = new ContainerBuilder;

        (new SymfonyLoaderAdapter($container))->registerProviders($this->extension([FakeSymfonyService::class]));

        $this->assertTrue($container->has(FakeSymfonyService::class));
        $this->assertInstanceOf(FakeSymfonyService::class, $container->get(FakeSymfonyService::class));
    }

    public function test_symfony_adapter_skips_missing_provider_classes(): void
    {
        $container = new ContainerBuilder;

        (new SymfonyLoaderAdapter($container))->registerProviders($this->extension(['No\\Such\\Service']));

        $this->assertFalse($container->has('No\\Such\\Service'));
    }
}
