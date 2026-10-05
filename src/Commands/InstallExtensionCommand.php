<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Commands;

use Override;
use Throwable;
use Symfony\Component\Console\Input\InputArgument;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\Package\Management\ExtensionManager;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;

final class InstallExtensionCommand extends Command
{
    use SupportsNamespacedNames;

    protected $name = 'laranail::package-management.install';

    /**
     * The bare name this command answered to before the `laranail::` shape. It stays registered
     * and prints a line naming the replacement when used.
     *
     * @deprecated `package-management:install` is removed no earlier than the next minor after 0.1.
     *
     * @var list<string>
     */
    protected array $deprecatedCommandAliases = ['package-management:install'];

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
