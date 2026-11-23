<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\DTOs;

use App\Modules\Catalog\Application\DTOs\CategorySection;
use App\Modules\Catalog\Application\DTOs\ProductCard;

/**
 * Everything the home page shows. An empty list means: leave this section out.
 */
final readonly class HomePage
{
    /**
     * @param  list<ContentItemView>  $heroSlides
     * @param  list<ContentItemView>  $trustBadges
     * @param  list<ContentItemView>  $banners
     * @param  list<ProductCard>  $hotProducts
     * @param  list<CategorySection>  $tabs
     * @param  list<CategorySection>  $rows
     * @param  list<ContentItemView>  $gallery
     */
    public function __construct(
        public SiteSettings $settings,
        public array $heroSlides,
        public array $trustBadges,
        public array $banners,
        public array $hotProducts,
        public array $tabs,
        public array $rows,
        public array $gallery,
    ) {}
}
