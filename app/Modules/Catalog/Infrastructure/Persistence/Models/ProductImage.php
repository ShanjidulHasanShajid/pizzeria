<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A row of product_images. Paths are relative to the public disk.
 *
 * @property int $id
 * @property int $product_id
 * @property string $path
 * @property string|null $thumbnail_path
 * @property bool $is_main
 * @property int $sort
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ProductImage extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'path',
        'thumbnail_path',
        'is_main',
        'sort',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'is_main' => 'boolean',
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
}
