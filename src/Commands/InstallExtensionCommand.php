<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Commands;

use Override;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;
use Simtabi\Laranail\Package\Management\ExtensionManager;
use Symfony\Component\Console\Input\InputArgument;
use Throwable;

final class InstallExtensionCommand extends Command
{
    use SupportsNamespacedNames;

    protected $name = 'laranail::package-management.install';

    protected $aliases = ['package-management:install'];

    protected $description = 'Install an extension: activate it and run its migrations.';

    public function handle(ExtensionManager $manager): int
    {
        $id = is_string($value = $this->argument('id')) ? $value : '';

        try {
            $manager->install($id);
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf('Installed [%s].', $id));

        return self::SUCCESS;
    }

    /** @return array<int, array<int, mixed>> */
    #[Override]
    protected function getArguments()
    {
        return [
            ['id', InputArgument::REQUIRED, 'The extension id (composer name / module alias / plugin id).'],
        ];
    }
}
