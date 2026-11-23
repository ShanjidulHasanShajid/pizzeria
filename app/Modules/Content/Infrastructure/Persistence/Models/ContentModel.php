<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * What the rows of all home page lists have in common. The other columns are the fields of their ContentType.
 *
 * @property int $id
 * @property int $sort
 * @property bool $is_active
 * @property Carbon|null $deleted_at
 */
abstract class ContentModel extends Model
{
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'sort' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
