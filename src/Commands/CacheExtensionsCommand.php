<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Commands;

use Override;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;
use Simtabi\Laranail\Package\Management\ExtensionRepository;
use Symfony\Component\Console\Input\InputOption;

final class CacheExtensionsCommand extends Command
{
    use SupportsNamespacedNames;

    protected $name = 'laranail::package-management.cache';

    protected $aliases = ['package-management:cache'];

    protected $description = 'Compile the discovered-extensions cache (or --clear it).';

    public function handle(ExtensionRepository $repository): int
    {
        if ($this->option('clear')) {
            $repository->clearCache();
            $this->components->info('Extension cache cleared.');

            return self::SUCCESS;
        }

        $count = $repository->rebuildCache();
        $this->components->info(sprintf('Cached %d extension(s).', $count));

        return self::SUCCESS;
    }

    /** @return array<int, array<int, mixed>> */
    #[Override]
    protected function getOptions()
    {
        return [
            ['clear', null, InputOption::VALUE_NONE, 'Delete the compiled cache instead of building it.'],
        ];
    }
}
