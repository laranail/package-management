<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Database\Seeders;

use Illuminate\Database\Seeder;
use Simtabi\Laranail\Package\Management\ExtensionRepository;
use Simtabi\Laranail\Package\Management\Models\ExtensionState;

/**
 * Seeds an inactive state row for every discovered extension so an admin surface can
 * list + activate them. Idempotent — safe to run repeatedly. Run explicitly:
 *   php artisan db:seed --class="Simtabi\Laranail\Package\Management\Database\Seeders\ExtensionStateSeeder"
 */
final class ExtensionStateSeeder extends Seeder
{
    public function __construct(private readonly ExtensionRepository $extensions) {}

    public function run(): void
    {
        foreach ($this->extensions->all() as $extension) {
            ExtensionState::query()->firstOrCreate(
                ['name' => $extension->id],
                ['is_active' => false, 'version' => $extension->version],
            );
        }
    }
}
