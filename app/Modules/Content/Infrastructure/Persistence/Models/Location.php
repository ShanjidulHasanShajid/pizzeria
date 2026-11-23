<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A row of locations.
 *
 * @property int $id
 * @property string $name
 * @property string $address
 * @property string|null $area
 * @property string $phone
 * @property string|null $map_embed_url
 * @property string|null $latitude
 * @property string|null $longitude
 * @property array<string, array{string, string}|null> $opening_hours
 * @property bool $is_pickup_available
 * @property int $sort
 * @property bool $is_active
 * @property Carbon|null $deleted_at
 */
class Location extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name', 'address', 'area', 'phone', 'map_embed_url', 'latitude', 'longitude',
        'opening_hours', 'is_pickup_available', 'sort', 'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'string',
            'longitude' => 'string',
            'opening_hours' => 'array',
            'is_pickup_available' => 'boolean',
            'sort' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
