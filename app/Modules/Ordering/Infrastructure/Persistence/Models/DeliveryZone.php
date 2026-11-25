<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A row of delivery_zones. Storage only: the rules live in Domain\Entities\DeliveryZone.
 *
 * @property int $id
 * @property int $location_id
 * @property string $name
 * @property int $fee poisha
 * @property int $min_order poisha
 * @property int $sort
 * @property bool $is_active
 * @property Carbon|null $deleted_at
 */
class DeliveryZone extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = ['location_id', 'name', 'fee', 'min_order', 'sort', 'is_active'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'location_id' => 'integer',
            'fee' => 'integer',
            'min_order' => 'integer',
            'sort' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
