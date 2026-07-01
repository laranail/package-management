<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Actions;

use Illuminate\Support\Facades\DB;
use Simtabi\Laranail\Package\Management\Services\ExtensionStateService;

/** Persists an extension as inactive (write use-case, transactional). */
final readonly class DeactivateExtension
{
    public function __construct(private ExtensionStateService $states) {}

    public function __invoke(string $name): void
    {
        DB::transaction(fn () => $this->states->deactivate($name));
    }

    public function handle(string $name): void
    {
        $this($name);
    }
}
