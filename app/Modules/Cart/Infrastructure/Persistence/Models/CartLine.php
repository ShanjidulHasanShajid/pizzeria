<?php

declare(strict_types=1);

namespace App\Modules\Cart\Infrastructure\Persistence\Models;

use App\Modules\Cart\Domain\Enums\CartLineType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A row of cart_lines. The price is in poisha.
 *
 * @property int $id
 * @property int $cart_id
 * @property CartLineType $type
 * @property int $product_id
 * @property int $variant_id
 * @property array<int, list<int>> $selections
 * @property string|null $note
 * @property int $quantity
 * @property int $last_unit_price
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CartLine extends Model
{
    /**
     * cart_id is not here: lines are always created through $cart->lines()->create().
     *
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'product_id',
        'variant_id',
        'selections',
        'note',
        'quantity',
        'last_unit_price',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cart_id' => 'integer',
            'type' => CartLineType::class,
            'product_id' => 'integer',
            'variant_id' => 'integer',
            'selections' => 'array',
            'quantity' => 'integer',
            'last_unit_price' => 'integer',
        ];
    }
}
