<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Database\Seeders\CategorySeeder;
use App\Modules\Catalog\Infrastructure\Database\Seeders\KitchenStationSeeder;
use App\Modules\Catalog\Infrastructure\Database\Seeders\MenuSeeder;
use App\Modules\Catalog\Infrastructure\Database\Seeders\PieSeeder;
use App\Modules\Catalog\Infrastructure\Database\Seeders\PizzaSeeder;
use App\Modules\Catalog\Infrastructure\Database\Seeders\SizeTierSeeder;
use App\Modules\Catalog\Infrastructure\Database\Seeders\TagSeeder;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Customization\Application\DTOs\ProductOptionGroupView;
use App\Modules\Customization\Application\DTOs\QuoteLineInput;
use App\Modules\Customization\Application\Queries\ProductOptionsQuery;
use App\Modules\Customization\Application\UseCases\QuoteLine;
use App\Modules\Customization\Infrastructure\Database\Seeders\CustomizationDevelopmentSeeder;

beforeEach(function (): void {
    // The catalog seeders without the images (drawing 66 pictures is slow and not needed here).
    $this->seed([
        KitchenStationSeeder::class,
        SizeTierSeeder::class,
        TagSeeder::class,
        CategorySeeder::class,
        PizzaSeeder::class,
        PieSeeder::class,
        MenuSeeder::class,
        CustomizationDevelopmentSeeder::class,
    ]);

    $this->groupsOf = fn (string $slug): array => array_map(
        fn (ProductOptionGroupView $group): string => $group->title.($group->isRequired ? ' (required)' : ''),
        app(ProductOptionsQuery::class)->forProduct(ProductModel::query()->where('slug', $slug)->firstOrFail()->id),
    );
});

it('offers the sample groups on the right products', function (): void {
    expect(($this->groupsOf)('margherita'))->toBe(['Choose your crust (required)', 'Extra toppings'])
        ->and(($this->groupsOf)('spicy-chicken'))->toBe(['Choose your crust (required)', 'Extra toppings'])
        ->and(($this->groupsOf)('beef-pie'))->toBe(['Extra cheese', 'Add a dip'])
        ->and(($this->groupsOf)('chicken-wings'))->toBe(['Choose your flavour (required)'])
        ->and(($this->groupsOf)('greek-salad'))->toBe(['Choose your dressing (required)'])
        ->and(($this->groupsOf)('vanilla-milkshake'))->toBe(['Make it special'])
        ->and(($this->groupsOf)('cappuccino'))->toBe(['Make it special'])
        ->and(($this->groupsOf)('french-fries'))->toBe([]);
    $this->assertDatabaseCount('option_groups', 11);
});

it('prices a seeded pizza, and changes nothing when it runs again', function (): void {
    $margherita = ProductModel::query()->where('slug', 'margherita')->firstOrFail();
    $crust = app(ProductOptionsQuery::class)->forProduct($margherita->id)[0];
    $stuffed = $crust->options[2];

    $line = app(QuoteLine::class)->handle(new QuoteLineInput(
        $margherita->id,
        $margherita->variants()->where('label', 'Medium')->firstOrFail()->id,
        [$crust->optionGroupId => [$stuffed->id]],
        quantity: 1,
    ));

    expect($stuffed->name)->toBe('Stuffed')
        ->and($line->unitPrice->poisha())->toBe(100_000); // 850 + 150

    $this->seed(CustomizationDevelopmentSeeder::class);

    $this->assertDatabaseCount('option_groups', 11);
});
