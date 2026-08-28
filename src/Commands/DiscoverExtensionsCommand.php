<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Commands;

use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\Package\Management\ExtensionManager;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;

final class DiscoverExtensionsCommand extends Command
{
    use SupportsNamespacedNames;

    protected $name = 'laranail::package-management.discover';

    protected $aliases = ['package-management:discover'];

    protected $description = 'Rescan the platform paths and rebuild the compiled manifest cache.';

    public function handle(ExtensionManager $manager): int
    {
        $count = $manager->discover();

        $this->components->info(sprintf('Discovered %d extension(s); manifest cache rebuilt.', $count));

        return self::SUCCESS;
    }
}
