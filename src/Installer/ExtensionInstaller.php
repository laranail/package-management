<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Installer;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use PharData;
use RuntimeException;
use Simtabi\Laranail\Package\Management\Contracts\LoaderAdapter;
use Simtabi\Laranail\Package\Management\Contracts\RunsMigrations;
use Simtabi\Laranail\Package\Management\Extension;
use Simtabi\Laranail\Package\Management\ExtensionManager;
use Simtabi\Laranail\Package\Management\ExtensionRepository;
use Simtabi\Laranail\Package\Management\Manifests\ManifestReader;
use Throwable;

/**
 * Installs an extension from a VCS archive into `platform/{role}s/{name}` (lowercase) and
 * runs it through the lifecycle. Every step registers an undo on a {@see RollbackStack};
 * any failure unwinds it so no files or tables are orphaned.
 */
final readonly class ExtensionInstaller
{
    /** @var array<string, string> role → manifest filename that identifies it */
    private const array MANIFESTS = ['plugin' => 'plugin.json', 'module' => 'module.json', 'package' => 'composer.json'];

    public function __construct(
        private SourceDriverManager $sources,
        private ExtensionManager $manager,
        private ExtensionRepository $repository,
        private ManifestReader $reader,
        private LoaderAdapter $adapter,
        private Filesystem $files,
        private Application $app,
    ) {}

    /**
     * @param  callable(string):bool|null  $confirmOverwrite  called with the target dir when it exists
     *                                                        and `$force` is false; return true to overwrite
     */
    public function install(RepositoryRef $ref, ?string $asRole = null, bool $force = false, ?callable $confirmOverwrite = null): Extension
    {
        $stack = new RollbackStack;
        $temp = $this->tempDir();
        $stack->push(fn () => $this->files->deleteDirectory($temp));

        try {
            $archive = $this->sources->forRef($ref)->download($ref, $temp);
            $this->verify($archive);

            $root = $this->locateRoot($this->extract($archive, $temp . '/extracted'));

            $role = $asRole ?? $this->detectRole($root);
            $name = $this->detectName($root, $role);
            $target = $this->targetDir($role, $name);

            $this->placeTarget($root, $target, $force, $confirmOverwrite, $temp, $stack);

            $this->repository->rebuildCache();
            $stack->push(fn (): int => $this->repository->rebuildCache());

            $extension = $this->reader->read($target, $role);
            if (! $extension instanceof Extension) {
                throw new RuntimeException("The installed artifact at [{$target}] has no valid {$role} manifest.");
            }

            // push the undo BEFORE install so a partial install (e.g. a migration throwing
            // after activation) is still fully unwound
            $stack->push(function () use ($extension): void {
                if ($this->adapter instanceof RunsMigrations) {
                    $this->adapter->rollbackMigrations($extension);
                }
                $this->manager->remove($extension->id);
            });
            $this->manager->install($extension->id);

            $stack->commit();
            $this->files->deleteDirectory($temp);

            return $extension->withEnabled(true);
        } catch (Throwable $e) {
            $stack->unwind();

            throw $e;
        }
    }

    private function placeTarget(string $root, string $target, bool $force, ?callable $confirm, string $temp, RollbackStack $stack): void
    {
        if ($this->files->isDirectory($target)) {
            $overwrite = $force || ($confirm !== null && $confirm($target));
            if (! $overwrite) {
                throw new RuntimeException("Target [{$target}] already exists (use --force to overwrite).");
            }

            // back the existing target up so a rollback can restore it
            $backup = $temp . '/backup';
            $this->files->moveDirectory($target, $backup);
            $stack->push(function () use ($target, $backup): void {
                $this->files->deleteDirectory($target);
                $this->files->moveDirectory($backup, $target);
            });
        }

        $this->files->ensureDirectoryExists(dirname($target));
        $this->files->moveDirectory($root, $target);
        $stack->push(fn () => $this->files->deleteDirectory($target));
    }

    private function verify(string $archive): void
    {
        $size = @filesize($archive);

        if ($size === false || $size === 0) {
            throw new RuntimeException('Downloaded archive is empty.');
        }

        $max = (int) $this->app->make('config')->get('laranail.package-management.installer.max_bytes', 104857600);
        if ($size > $max) {
            throw new RuntimeException("Downloaded archive exceeds max_bytes ({$size} > {$max}).");
        }
    }

    private function extract(string $archive, string $dest): string
    {
        $this->files->ensureDirectoryExists($dest);
        (new PharData($archive))->extractTo($dest, null, true);

        return $dest;
    }

    /** VCS tarballs wrap the repo in a single top-level dir; unwrap to the dir that holds a manifest. */
    private function locateRoot(string $dir): string
    {
        if ($this->hasAnyManifest($dir)) {
            return $dir;
        }

        $subdirs = $this->files->directories($dir);
        if (count($subdirs) === 1 && $this->hasAnyManifest($subdirs[0])) {
            return $subdirs[0];
        }

        throw new RuntimeException('No manifest (composer.json / module.json / plugin.json) found in the downloaded archive.');
    }

    private function hasAnyManifest(string $dir): bool
    {
        return array_any(self::MANIFESTS, fn (string $file) => $this->files->isFile($dir . '/' . $file));
    }

    private function detectRole(string $root): string
    {
        foreach (self::MANIFESTS as $role => $file) {
            if ($this->files->isFile($root . '/' . $file)) {
                return $role;
            }
        }

        throw new RuntimeException("Cannot detect the role of the downloaded artifact at [{$root}].");
    }

    private function detectName(string $root, string $role): string
    {
        $extension = $this->reader->read($root, $role);
        if ($extension instanceof Extension) {
            return $extension->name !== '' ? $extension->name : Str::afterLast($extension->id, '/');
        }

        throw new RuntimeException("The downloaded [{$role}] artifact has no valid manifest.");
    }

    /** Install into the same directory the loader discovers ({role}s from config.paths), lowercase name. */
    private function targetDir(string $role, string $name): string
    {
        $root = (string) $this->app->make('config')->get(
            "laranail.package-management.paths.{$role}s",
            $this->app->basePath("platform/{$role}s"),
        );

        return rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . Str::slug($name);
    }

    private function tempDir(): string
    {
        $dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . '/laranail-install-' . bin2hex(random_bytes(6));
        $this->files->ensureDirectoryExists($dir);

        return $dir;
    }
}
