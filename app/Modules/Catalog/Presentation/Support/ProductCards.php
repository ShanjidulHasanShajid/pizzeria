<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Support;

use App\Modules\Catalog\Application\DTOs\ProductCard;
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
     * @return array{name: string, slug: string, description: string, price: int, isFromPrice: bool, state: string, badges: list<array{variant: string, label: string}>, image: string}
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
            'image' => $card->imagePath === null ? asset('images/placeholder.svg') : $this->files->url($card->imagePath),
        ];
    }

    /**
     * "view" is for an item with one size: whether it has options is known on its page.
     * "Add to cart" directly on the card comes with the cart (Phases 10 and 11).
     */
    private function state(ProductCard $card): string
    {
        return match (true) {
            ! $card->isAvailable => 'soldout',
            $card->type === ProductType::Combo => 'combo',
            $card->type === ProductType::Builder, count($card->variantLabels) > 1 => 'options',
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

        foreach ($card->tags as $tag) {
            $badges[] = [
                'variant' => match ($tag->type) {
                    TagType::Dietary => 'veg',
                    TagType::Flavour => 'spicy',
                    TagType::Allergen => 'neutral',
                },
                'label' => $tag->name,
            ];
        }

        return $badges;
    }
}
