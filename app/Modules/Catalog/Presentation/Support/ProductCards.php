<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Support;

use App\Modules\Catalog\Application\DTOs\ProductCard;
use App\Modules\Catalog\Application\DTOs\TagBadge;
use App\Modules\Catalog\Domain\Enums\LeadTimeClass;
use App\Modules\Catalog\Domain\Enums\ProductType;
use App\Modules\Catalog\Domain\Enums\TagType;
use App\Modules\Shared\Application\Ports\FileStorage;

/**
 * Turns a ProductCard into what the product-card component draws.
 */
final class ProductCards
{
    public function __construct(private readonly FileStorage $files) {}

    /**
     * @return array{name: string, slug: string, description: string, price: int, isFromPrice: bool, state: string, badges: list<array{variant: string, label: string}>, sizes: list<string>, tags: list<array{label: string, icon: ?string, class: string}>, image: string}
     */
    public function toArray(ProductCard $card): array
    {
        return [
            'name' => $card->name,
            'slug' => $card->slug,
            'description' => $card->shortDescription ?? '',
            'price' => $card->fromPrice->poisha(),
            'isFromPrice' => $card->hasSeveralPrices,
            'state' => $this->state($card),
            'badges' => $this->badges($card),
            'sizes' => count($card->variantLabels) > 1 ? $card->variantLabels : [],
            'tags' => array_map(fn (TagBadge $tag): array => [
                'label' => $tag->name,
                'icon' => $tag->icon,
                'class' => match ($tag->type) {
                    TagType::Dietary => 'text-accent-700',
                    TagType::Flavour => 'text-brand-700',
                    TagType::Allergen => 'text-amber-800',
                },
            ], $card->tags),
            'image' => $card->imagePath === null ? asset('images/placeholder.svg') : $this->files->url($card->imagePath),
        ];
    }

    /**
     * "view" is a product with nothing to choose. Its button becomes "Add to cart" in Phase 11.
     */
    private function state(ProductCard $card): string
    {
        return match (true) {
            ! $card->isAvailable => 'soldout',
            $card->type === ProductType::Combo => 'combo',
            $card->type === ProductType::Builder, $card->hasOptions, count($card->variantLabels) > 1 => 'options',
            default => 'view',
        };
    }

    /**
     * @return list<array{variant: string, label: string}>
     */
    private function badges(ProductCard $card): array
    {
        $badges = [];

        if ($card->isNew) {
            $badges[] = ['variant' => 'new', 'label' => 'New'];
        }

        if ($card->isHot) {
            $badges[] = ['variant' => 'hot', 'label' => 'Hot'];
        }

        if ($card->leadTimeClass !== LeadTimeClass::MadeToOrder) {
            $badges[] = ['variant' => 'lead', 'label' => $card->leadTimeLabel];
        }

        return $badges;
    }
}
