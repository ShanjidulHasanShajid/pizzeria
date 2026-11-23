<?php

declare(strict_types=1);

namespace App\Modules\Customization\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A row of option_prices: what an option costs at one size, in poisha.
 *
 * @property int $id
 * @property int $option_id
 * @property int $size_tier_id
 * @property int $price
 */
class OptionPrice extends Model
{
    public $timestamps = false;

    /**
     * option_id is not here: prices are always saved through $option->prices().
     *
     * @var list<string>
     */
    protected $fillable = [
        'size_tier_id',
        'price',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'option_id' => 'integer',
            'size_tier_id' => 'integer',
            'price' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Option, $this>
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }
}
