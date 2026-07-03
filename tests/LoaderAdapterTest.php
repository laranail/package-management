<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Simtabi\Laranail\Package\Management\Adapters\LumenLoaderAdapter;
use Simtabi\Laranail\Package\Management\Adapters\SymfonyLoaderAdapter;
use Simtabi\Laranail\Package\Management\Extension;
use Simtabi\Laranail\Package\Management\Tests\Fixtures\FakeProvider;
use Simtabi\Laranail\Package\Management\Tests\Fixtures\FakeSymfonyBootableProvider;
use Simtabi\Laranail\Package\Management\Tests\Fixtures\FakeSymfonyContainerAwareProvider;
use Simtabi\Laranail\Package\Management\Tests\Fixtures\FakeSymfonyContract;
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

    public function test_symfony_adapter_injects_the_container_into_a_constructor_arg_provider(): void
    {
        $container = new ContainerBuilder;

        (new SymfonyLoaderAdapter($container))->registerProviders($this->extension([FakeSymfonyContainerAwareProvider::class]));

        $instance = $container->get(FakeSymfonyContainerAwareProvider::class);
        $this->assertInstanceOf(FakeSymfonyContainerAwareProvider::class, $instance);
        $this->assertSame($container, $instance->container); // the container was injected
    }

    public function test_symfony_adapter_also_registers_under_implemented_interfaces(): void
    {
        $container = new ContainerBuilder;

        (new SymfonyLoaderAdapter($container))->registerProviders($this->extension([FakeSymfonyContainerAwareProvider::class]));

        // resolvable by the interface, not just the concrete FQCN
        $this->assertTrue($container->has(FakeSymfonyContract::class));
        $this->assertInstanceOf(FakeSymfonyContract::class, $container->get(FakeSymfonyContract::class));
    }

    public function test_symfony_adapter_invokes_register_and_boot(): void
    {
        $container = new ContainerBuilder;

        (new SymfonyLoaderAdapter($container))->registerProviders($this->extension([FakeSymfonyBootableProvider::class]));

        $this->assertTrue($container->has('sf.registered')); // register() ran with the container
        $this->assertTrue($container->has('sf.booted'));     // boot() ran with the container
    }

    public function test_symfony_adapter_is_a_noop_on_a_compiled_container(): void
    {
        $container = new ContainerBuilder;
        $container->compile();

        // must not fatal — a compiled container is sealed; register via a CompilerPass instead
        (new SymfonyLoaderAdapter($container))->registerProviders($this->extension([FakeSymfonyService::class]));

        $this->assertFalse($container->has(FakeSymfonyService::class));
    }

    public function test_symfony_adapter_registers_runtime_autoload(): void
    {
        $dir = sys_get_temp_dir() . '/laranail-pm-sfauto-' . uniqid();
        @mkdir($dir . '/src', 0777, true);
        file_put_contents($dir . '/src/Widget.php', "<?php\nnamespace SfAuto;\nclass Widget {}\n");

        $extension = new Extension('sfauto', 'SfAuto', 'SfAuto\\', [], '1.0.0', [], 'plugin', $dir, true);
        (new SymfonyLoaderAdapter(new ContainerBuilder))->registerAutoload($extension);

        $this->assertTrue(class_exists('SfAuto\\Widget')); // PSR-4 registered at runtime, no composer dump

        @unlink($dir . '/src/Widget.php');
        @rmdir($dir . '/src');
        @rmdir($dir);
    }
}
