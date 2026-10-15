<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Database\Seeders;

use App\Modules\Catalog\Domain\Entities\Tag;
use App\Modules\Catalog\Domain\Enums\TagType;
use App\Modules\Catalog\Domain\Repositories\TagRepository;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Illuminate\Database\Seeder;

/**
 * Dietary, allergen and flavour labels. Icons are names from the <x-icon> component.
 */
final class TagSeeder extends Seeder
{
    /** slug => [name, type, icon] */
    private const TAGS = [
        'veg' => ['Veg', TagType::Dietary, 'check-circle'],
        'non-veg' => ['Non-veg', TagType::Dietary, null],
        'halal' => ['Halal', TagType::Dietary, 'shield-check'],
        'spicy' => ['Spicy', TagType::Flavour, 'fire'],
        'contains-nuts' => ['Contains Nuts', TagType::Allergen, 'exclamation-triangle'],
        'contains-dairy' => ['Contains Dairy', TagType::Allergen, 'exclamation-triangle'],
        'contains-gluten' => ['Contains Gluten', TagType::Allergen, 'exclamation-triangle'],
        'seafood' => ['Seafood', TagType::Allergen, 'exclamation-triangle'],
    ];

    public function __construct(private readonly TagRepository $tags) {}

    public function run(): void
    {
        $sort = SortOrder::first();

        foreach (self::TAGS as $slug => [$name, $type, $icon]) {
            $this->tags->save(Tag::create($name, Slug::fromString($slug), $type, $sort, $icon));
            $sort = $sort->next();
        }
    }
}
