<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Stores;

use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\Package\Management\Contracts\ActivationStore;

/**
 * Default activation store: a JSON array of active extension ids. No database
 * requirement. Writes atomically so an interrupted write can't corrupt state.
 */
final readonly class FileActivationStore implements ActivationStore
{
    public function __construct(
        private Filesystem $files,
        private string $path,
    ) {}

    /** @return list<string> */
    public function active(): array
    {
        if (! $this->files->isFile($this->path)) {
            return [];
        }

        $data = json_decode((string) $this->files->get($this->path), true);

        return is_array($data) ? array_values(array_filter($data, is_string(...))) : [];
    }

    public function isActive(string $id): bool
    {
        return in_array($id, $this->active(), true);
    }

    public function activate(string $id): void
    {
        $active = $this->active();

        if (! in_array($id, $active, true)) {
            $active[] = $id;
            $this->write($active);
        }
    }

    public function deactivate(string $id): void
    {
        $this->write(array_values(array_diff($this->active(), [$id])));
    }

    public function forget(string $id): void
    {
        // the file store's only state is active-list membership
        $this->deactivate($id);
    }

    /** @param  list<string>  $ids */
    private function write(array $ids): void
    {
        $this->files->ensureDirectoryExists(dirname($this->path));

        $encoded = json_encode(array_values($ids), JSON_PRETTY_PRINT);
        $tmp = $this->path . '.tmp' . getmypid();
        $this->files->put($tmp, $encoded === false ? '[]' : $encoded);
        $this->files->move($tmp, $this->path);
    }
}
