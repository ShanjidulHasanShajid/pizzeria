<?php

declare(strict_types=1);

namespace App\Modules\Customization\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A row of option_groups.
 *
 * @property int $id
 * @property string $name
 * @property string $display_title
 * @property string|null $description
 * @property int $sort
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Option> $options
 * @property-read int|null $options_count only after withCount('options')
 * @property-read int|null $sold_out_count only after withCount('options as sold_out_count')
 * @property-read int|null $attachments_count only after withCount('attachments')
 */
class OptionGroup extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'display_title',
        'description',
        'sort',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Option, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(Option::class);
    }

    /**
     * Where this group is attached to products.
     *
     * @return HasMany<ProductOptionGroup, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ProductOptionGroup::class);
    }
}
