<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests\Fixtures;

use stdClass;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class FakeSymfonyBootableProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->set('sf.registered', new stdClass);
    }

    public function boot(ContainerInterface $container): void
    {
        $container->set('sf.booted', new stdClass);
    }
}
