<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Commands;

use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;
use Simtabi\Laranail\Package\Management\ExtensionManager;

final class DiscoverExtensionsCommand extends Command
{
    use SupportsNamespacedNames;

    protected $name = 'laranail::package-management.discover';

    protected $aliases = ['package-management:discover'];

    protected $description = 'Rescan the platform paths and report discovered extensions.';

    public function handle(ExtensionManager $manager): int
    {
        $all = $manager->all();

        $this->components->info(sprintf('Discovered %d extension(s).', count($all)));

        return self::SUCCESS;
    }
}
