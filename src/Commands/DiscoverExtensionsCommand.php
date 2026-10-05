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

    /**
     * The bare name this command answered to before the `laranail::` shape. It stays registered
     * and prints a line naming the replacement when used.
     *
     * @deprecated `package-management:discover` is removed no earlier than the next minor after 0.1.
     *
     * @var list<string>
     */
    protected array $deprecatedCommandAliases = ['package-management:discover'];

    protected $description = 'Rescan the platform paths and rebuild the compiled manifest cache.';

    public function handle(ExtensionManager $manager): int
    {
        $count = $manager->discover();

        $this->components->info(sprintf('Discovered %d extension(s); manifest cache rebuilt.', $count));

        return self::SUCCESS;
    }
}
