<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests\Fixtures;

use Illuminate\Contracts\Container\Container;

/**
 * A minimal service-provider-shaped class (register + boot) used to prove the
 * LumenLoaderAdapter's manual registration path against a bare container.
 */
final class FakeProvider
{
    public function __construct(private readonly Container $app) {}

    public function register(): void
    {
        $this->app->instance('fake.registered', true);
    }

    public function boot(): void
    {
        $this->app->instance('fake.booted', true);
    }
}
