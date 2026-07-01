<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Commands;

use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;
use Simtabi\Laranail\Package\Management\ExtensionManager;
use Symfony\Component\Console\Input\InputArgument;
use Throwable;

final class DisableExtensionCommand extends Command
{
    use SupportsNamespacedNames;

    protected $name = 'laranail::package-management.disable';

    protected $aliases = ['package-management:disable'];

    protected $description = 'Deactivate an extension (guarded by reverse dependencies).';

    public function handle(ExtensionManager $manager): int
    {
        try {
            $manager->disable((string) $this->argument('id'));
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf('Disabled [%s].', $this->argument('id')));

        return self::SUCCESS;
    }

    /** @return array<int, array<int, mixed>> */
    protected function getArguments()
    {
        return [
            ['id', InputArgument::REQUIRED, 'The extension id.'],
        ];
    }
}
