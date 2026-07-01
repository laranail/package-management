<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Commands;

use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;
use Simtabi\Laranail\Package\Management\ExtensionManager;
use Symfony\Component\Console\Input\InputArgument;
use Throwable;

final class EnableExtensionCommand extends Command
{
    use SupportsNamespacedNames;

    protected $name = 'laranail::package-management.enable';

    protected $aliases = ['package-management:enable'];

    protected $description = 'Activate an extension (and verify its dependencies).';

    public function handle(ExtensionManager $manager): int
    {
        try {
            $manager->enable((string) $this->argument('id'));
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf('Enabled [%s].', $this->argument('id')));

        return self::SUCCESS;
    }

    /** @return array<int, array<int, mixed>> */
    protected function getArguments()
    {
        return [
            ['id', InputArgument::REQUIRED, 'The extension id (composer name / module alias / plugin id).'],
        ];
    }
}
