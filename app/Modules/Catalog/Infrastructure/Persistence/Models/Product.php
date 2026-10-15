<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence\Models;

use App\Modules\Catalog\Domain\Enums\LeadTimeClass;
use App\Modules\Catalog\Domain\Enums\ProductType;
use App\Modules\Catalog\Infrastructure\Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A row of products. Storage only: the rules live in Domain\Entities\Product.
 *
 * @property int $id
 * @property int $category_id
 * @property ProductType $type
 * @property string $name
 * @property string $slug
 * @property string|null $short_description
 * @property string|null $description
 * @property LeadTimeClass $lead_time_class
 * @property int $lead_time_minutes
 * @property bool $is_new
 * @property bool $is_hot
 * @property bool $is_giftable
 * @property bool $is_published
 * @property bool $is_available
 * @property int $sort
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read int|null $variants_count only after withCount('variants')
 * @property-read int|null $variants_min_price only after withMin('variants', 'price')
 * @property-read int|null $variants_max_price only after withMax('variants', 'price')
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'type',
        'name',
        'slug',
        'short_description',
        'description',
        'lead_time_class',
        'lead_time_minutes',
        'is_new',
        'is_hot',
        'is_giftable',
        'is_published',
        'is_available',
        'sort',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'type' => ProductType::class,
            'lead_time_class' => LeadTimeClass::class,
            'lead_time_minutes' => 'integer',
            'is_new' => 'boolean',
            'is_hot' => 'boolean',
            'is_giftable' => 'boolean',
            'is_published' => 'boolean',
            'is_available' => 'boolean',
            'sort' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Variants that are not deleted.
     *
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * Only the main image: product cards need just this one.
     *
     * @return HasOne<ProductImage, $this>
     */
    public function mainImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_main', true);
    }

    /**
     * Tags that are not deleted (what the shop shows).
     *
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tag');
    }

    /**
     * Also the links to tags in the Trash. The repository loads the aggregate with this,
     * so saving a product never drops a link to a deleted tag (it comes back on restore).
     *
     * @return BelongsToMany<Tag, $this>
     */
    public function tagsIncludingDeleted(): BelongsToMany
    {
        return $this->tags()->withTrashed();
    }
}
