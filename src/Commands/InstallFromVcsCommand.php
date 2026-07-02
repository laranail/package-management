<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Commands;

use Override;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;
use Simtabi\Laranail\Package\Management\Installer\ExtensionInstaller;
use Simtabi\Laranail\Package\Management\Installer\RepositoryRef;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

final class InstallFromVcsCommand extends Command
{
    use SupportsNamespacedNames;

    protected $name = 'laranail::package-management.install-from';

    protected $aliases = ['package-management:install-from'];

    protected $description = 'Install an extension from a VCS repository (GitHub / GitLab / Bitbucket).';

    public function handle(ExtensionInstaller $installer): int
    {
        $url = is_string($value = $this->argument('url')) ? $value : '';
        $ref = is_string($value = $this->option('ref')) ? $value : null;
        $as = is_string($value = $this->option('as')) ? $value : null;
        $token = is_string($value = $this->option('token')) ? $value : null;
        $default = (string) config('laranail.package-management.installer.default_provider', 'github');

        if ($as !== null && ! in_array($as, ['package', 'module', 'plugin'], true)) {
            $this->components->error('--as must be one of: package, module, plugin.');

            return self::FAILURE;
        }

        try {
            $repository = RepositoryRef::parse($url, $ref, $token, $default);

            $extension = $installer->install(
                $repository,
                $as,
                (bool) $this->option('force'),
                fn (string $target): bool => $this->confirm("Target [{$target}] already exists. Overwrite it?", false),
            );
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf('Installed [%s] as a %s.', $extension->id, $extension->role));

        return self::SUCCESS;
    }

    /** @return array<int, array<int, mixed>> */
    #[Override]
    protected function getArguments()
    {
        return [
            ['url', InputArgument::REQUIRED, 'owner/repo, or a full GitHub / GitLab / Bitbucket URL.'],
        ];
    }

    /** @return array<int, array<int, mixed>> */
    #[Override]
    protected function getOptions()
    {
        return [
            ['ref', null, InputOption::VALUE_REQUIRED, 'Branch, tag, or commit (default: the repo default branch).'],
            ['as', null, InputOption::VALUE_REQUIRED, 'Force the role: package | module | plugin (default: inferred from the manifest).'],
            ['token', null, InputOption::VALUE_REQUIRED, 'VCS access token for a private repository.'],
            ['force', null, InputOption::VALUE_NONE, 'Overwrite an existing target without prompting.'],
        ];
    }
}
