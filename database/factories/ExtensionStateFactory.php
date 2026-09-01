<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Simtabi\Laranail\Package\Management\Models\ExtensionState;

/**
 * @extends Factory<ExtensionState>
 */
class ExtensionStateFactory extends Factory
{
    protected $model = ExtensionState::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->slug(2),
            'is_active' => false,
            'version' => '1.0.0',
            'settings' => [],
            'installed_at' => null,
            'activated_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'is_active' => true,
            'installed_at' => now(),
            'activated_at' => now(),
        ]);
    }
}
