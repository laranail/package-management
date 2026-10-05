<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Commands;

use Override;
use Symfony\Component\Console\Input\InputOption;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\Package\Management\ExtensionRepository;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;

final class CacheExtensionsCommand extends Command
{
    use SupportsNamespacedNames;

    protected $name = 'laranail::package-management.cache';

    /**
     * The bare name this command answered to before the `laranail::` shape. It stays registered
     * and prints a line naming the replacement when used.
     *
     * @deprecated `package-management:cache` is removed no earlier than the next minor after 0.1.
     *
     * @var list<string>
     */
    protected array $deprecatedCommandAliases = ['package-management:cache'];

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
