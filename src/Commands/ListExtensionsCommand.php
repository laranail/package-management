<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Commands;

use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;
use Simtabi\Laranail\Package\Management\Extension;
use Simtabi\Laranail\Package\Management\ExtensionManager;

final class ListExtensionsCommand extends Command
{
    use SupportsNamespacedNames;

    protected $name = 'laranail::package-management.list';

    protected $aliases = ['package-management:list'];

    protected $description = 'List discovered extensions (role, version, state).';

    public function handle(ExtensionManager $manager): int
    {
        $rows = array_map(static fn (Extension $e): array => [
            $e->id, $e->role, $e->version, $e->enabled ? 'enabled' : 'disabled',
        ], $manager->all());

        if ($rows === []) {
            $this->components->info('No extensions discovered under the configured platform paths.');

            return self::SUCCESS;
        }

        $this->table(['ID', 'Role', 'Version', 'State'], $rows);

        return self::SUCCESS;
    }
}
