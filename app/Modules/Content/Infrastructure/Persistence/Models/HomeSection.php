<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A row of home_sections.
 *
 * @property int $id
 * @property string $kind
 * @property int $category_id
 * @property array<int, int>|null $product_ids
 * @property int $sort
 */
class HomeSection extends Model
{
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = ['kind', 'category_id', 'product_ids', 'sort'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'product_ids' => 'array',
            'sort' => 'integer',
        ];
    }
}
