<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Override;
use Simtabi\Laranail\Package\Management\Database\Factories\ExtensionStateFactory;

/**
 * Persistence record for one extension's activation state.
 *
 * @property int $id
 * @property string $name
 * @property bool $is_active
 * @property string|null $version
 * @property array<string, mixed>|null $settings
 * @property Carbon|null $installed_at
 * @property Carbon|null $activated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ExtensionState extends Model
{
    /** @use HasFactory<ExtensionStateFactory> */
    use HasFactory;

    protected $table = 'laranail_extension_states';

    /** @param  array<string, mixed>  $attributes */
    public function __construct(array $attributes = [])
    {
        // table + connection are config-driven so the package is drop-in reusable
        $this->setTable((string) config('laranail.package-management.activation.table', $this->table));

        $connection = config('laranail.package-management.activation.connection');
        if ($connection !== null) {
            $this->setConnection((string) $connection);
        }

        parent::__construct($attributes);
    }

    /** @var list<string> */
    protected $fillable = [
        'name',
        'is_active',
        'version',
        'settings',
        'installed_at',
        'activated_at',
    ];

    /**
     * @param  Builder<ExtensionState>  $query
     * @return Builder<ExtensionState>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @return array<string, string> */
    #[Override]
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
            'installed_at' => 'datetime',
            'activated_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ExtensionStateFactory
    {
        return ExtensionStateFactory::new();
    }
}
