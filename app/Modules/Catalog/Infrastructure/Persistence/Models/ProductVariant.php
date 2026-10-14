<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A row of product_variants. The price is in poisha.
 *
 * @property int $id
 * @property int $product_id
 * @property int|null $size_tier_id
 * @property string $label
 * @property int $price
 * @property bool $is_default
 * @property bool $is_available
 * @property int $sort
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class ProductVariant extends Model
{
    use SoftDeletes;

    /**
     * product_id is not here: variants are always created through $product->variants()->create(),
     * which fills it in.
     *
     * @var list<string>
     */
    protected $fillable = [
        'size_tier_id',
        'label',
        'price',
        'is_default',
        'is_available',
        'sort',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'size_tier_id' => 'integer',
            'price' => 'integer',
            'is_default' => 'boolean',
            'is_available' => 'boolean',
            'sort' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<SizeTier, $this>
     */
    public function sizeTier(): BelongsTo
    {
        return $this->belongsTo(SizeTier::class);
    }
}
