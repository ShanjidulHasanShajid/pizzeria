<?php

declare(strict_types=1);

namespace App\Modules\Customization\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A row of product_option_group: one group attached to one product, with its rules.
 * There is no product() relation, because Product belongs to the Catalog module.
 *
 * @property int $id
 * @property int $product_id
 * @property int $option_group_id
 * @property bool $is_required
 * @property int $min_select
 * @property int $max_select
 * @property int $free_count
 * @property int $sort
 * @property-read OptionGroup $optionGroup
 */
class ProductOptionGroup extends Model
{
    // Laravel would guess "product_option_groups".
    protected $table = 'product_option_group';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'option_group_id',
        'is_required',
        'min_select',
        'max_select',
        'free_count',
        'sort',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'option_group_id' => 'integer',
            'is_required' => 'boolean',
            'min_select' => 'integer',
            'max_select' => 'integer',
            'free_count' => 'integer',
            'sort' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<OptionGroup, $this>
     */
    public function optionGroup(): BelongsTo
    {
        return $this->belongsTo(OptionGroup::class);
    }
}
