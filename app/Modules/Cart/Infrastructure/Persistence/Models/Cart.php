<?php

declare(strict_types=1);

namespace App\Modules\Cart\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A row of carts. There is no user() relation, because User belongs to the Identity module.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $guest_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, CartLine> $lines
 */
class Cart extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'guest_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
        ];
    }

    /**
     * In the order they were added.
     *
     * @return HasMany<CartLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(CartLine::class)->orderBy('id');
    }
}
