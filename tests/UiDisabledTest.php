<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Tests;

/** The UI is opt-in — routes must not exist when `ui.enabled` is false (the default). */
class UiDisabledTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('laranail.package-management.ui.enabled', false);
        $app['config']->set('laranail.package-management.ui.prefix', 'ext');
    }

    public function test_ui_routes_are_absent_when_disabled(): void
    {
        $this->get('/ext')->assertNotFound();
    }
}
