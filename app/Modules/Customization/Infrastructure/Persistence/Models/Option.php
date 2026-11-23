<?php

declare(strict_types=1);

namespace App\Modules\Customization\Infrastructure\Persistence\Models;

use App\Modules\Customization\Domain\Enums\PriceMode;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A row of options. The flat price is in poisha.
 *
 * @property int $id
 * @property int $option_group_id
 * @property string $name
 * @property string|null $image_path
 * @property bool $is_veg
 * @property bool $is_available
 * @property PriceMode $price_mode
 * @property int|null $flat_price
 * @property int $sort
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, OptionPrice> $prices
 */
class Option extends Model
{
    use SoftDeletes;

    /**
     * option_group_id is not here: options are always created through $group->options()->create().
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'image_path',
        'is_veg',
        'is_available',
        'price_mode',
        'flat_price',
        'sort',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'option_group_id' => 'integer',
            'is_veg' => 'boolean',
            'is_available' => 'boolean',
            'price_mode' => PriceMode::class,
            'flat_price' => 'integer',
            'sort' => 'integer',
        ];
    }

    /**
     * @return HasMany<OptionPrice, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(OptionPrice::class);
    }
}
