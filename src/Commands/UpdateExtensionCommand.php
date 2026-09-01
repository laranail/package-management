<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Commands;

use Override;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;
use Simtabi\Laranail\Package\Management\ExtensionManager;
use Symfony\Component\Console\Input\InputArgument;
use Throwable;

final class UpdateExtensionCommand extends Command
{
    use SupportsNamespacedNames;

    protected $name = 'laranail::package-management.update';

    protected $aliases = ['package-management:update'];

    protected $description = 'Run any pending migrations for an already-installed extension.';

    public function handle(ExtensionManager $manager): int
    {
        $id = is_string($value = $this->argument('id')) ? $value : '';

        try {
            $manager->update($id);
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf('Updated [%s].', $id));

        return self::SUCCESS;
    }

    /** @return array<int, array<int, mixed>> */
    #[Override]
    protected function getArguments()
    {
        return [
            ['id', InputArgument::REQUIRED, 'The extension id.'],
        ];
    }
}
