<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A row of nav_items.
 *
 * @property int $id
 * @property string $route_key
 * @property string $label
 * @property int $sort
 * @property bool $is_visible
 * @property bool $is_highlighted
 */
class NavItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['route_key', 'label', 'sort', 'is_visible', 'is_highlighted'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort' => 'integer',
            'is_visible' => 'boolean',
            'is_highlighted' => 'boolean',
        ];
    }
}
