<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests\Fixtures;

use Symfony\Component\DependencyInjection\ContainerInterface;

final class FakeSymfonyContainerAwareProvider implements FakeSymfonyContract
{
    public function __construct(public readonly ?ContainerInterface $container = null) {}
}
