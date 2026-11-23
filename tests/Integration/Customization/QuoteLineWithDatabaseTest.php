<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductVariant as ProductVariantModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\SizeTier as SizeTierModel;
use App\Modules\Customization\Application\DTOs\QuoteLineInput;
use App\Modules\Customization\Application\UseCases\QuoteLine;
use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Entities\ProductOptionGroup;
use App\Modules\Customization\Domain\Exceptions\PricingFailed;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Customization\Domain\Repositories\ProductOptionGroupRepository;
use App\Modules\Customization\Domain\ValueObjects\SelectionRules;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

beforeEach(function (): void {
    $large = SizeTierModel::factory()->create(['name' => 'Large']);
    $this->category = CategoryModel::factory()->create();
    $this->product = ProductModel::factory()->for($this->category)
        ->has(ProductVariantModel::factory()->forSizeTier($large)->asDefault()->state(['price' => 125_000]), 'variants')
        ->create();

    $groups = app(OptionGroupRepository::class);
    $crust = OptionGroup::create('Crusts', 'Crust', SortOrder::first());
    $crust->addOption(Option::withPricesPerSize('Stuffed', [$large->id => Money::fromTaka('200')], SortOrder::first()));
    $crustId = $groups->save($crust);
    app(ProductOptionGroupRepository::class)->save(
        ProductOptionGroup::attach($this->product->id, $crustId, SelectionRules::exactlyOne(), SortOrder::first()),
    );

    $this->input = new QuoteLineInput(
        $this->product->id,
        $this->product->variants()->firstOrFail()->id,
        [$crustId => [$groups->find($crustId)->options()[0]->id()]],
        quantity: 2,
    );
});

it('prices a line with the real catalog and options', function (): void {
    $line = app(QuoteLine::class)->handle($this->input);

    expect($line->variantLabel)->toBe('Large')
        ->and($line->unitPrice->poisha())->toBe(145_000)
        ->and($line->lineTotal->poisha())->toBe(290_000);
});

it('refuses a product in an inactive category, because customers cannot see it', function (): void {
    $this->category->update(['is_active' => false]);

    app(QuoteLine::class)->handle($this->input);
})->throws(PricingFailed::class, 'is not on the menu');
