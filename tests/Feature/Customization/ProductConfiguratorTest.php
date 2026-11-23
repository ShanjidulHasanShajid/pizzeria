<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductVariant as ProductVariantModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\SizeTier as SizeTierModel;
use App\Modules\Customization\Application\Queries\ProductConfigurationQuery;
use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Entities\ProductOptionGroup;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Customization\Domain\Repositories\ProductOptionGroupRepository;
use App\Modules\Customization\Domain\ValueObjects\SelectionRules;
use App\Modules\Customization\Presentation\Livewire\ProductConfigurator;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/*
| A pizza in two sizes: Medium ৳500, Large ৳900.
| Crust, required, one: Thin (free), Stuffed (Large only, ৳200).
| Toppings, up to 2, 1 free: Corn ৳50, Beef ৳120, Olives (sold out, ৳60).
| Dip, optional, at most one: Garlic ৳40.
*/
beforeEach(function (): void {
    $medium = SizeTierModel::factory()->create(['name' => 'Medium']);
    $large = SizeTierModel::factory()->create(['name' => 'Large']);
    $this->category = CategoryModel::factory()->create();
    $this->product = ProductModel::factory()->for($this->category)
        ->has(ProductVariantModel::factory()->forSizeTier($medium)->asDefault()->state(['label' => 'Medium', 'price' => 50_000, 'sort' => 10]), 'variants')
        ->has(ProductVariantModel::factory()->forSizeTier($large)->state(['label' => 'Large', 'price' => 90_000, 'sort' => 20]), 'variants')
        ->create(['name' => 'Margherita']);
    $this->medium = $this->product->variants()->where('label', 'Medium')->firstOrFail()->id;
    $this->large = $this->product->variants()->where('label', 'Large')->firstOrFail()->id;

    $this->groups = app(OptionGroupRepository::class);
    $attach = function (OptionGroup $group, SelectionRules $rules, int $sort): int {
        $id = $this->groups->save($group);
        app(ProductOptionGroupRepository::class)->save(ProductOptionGroup::attach($this->product->id, $id, $rules, SortOrder::fromInt($sort)));

        return $id;
    };

    $crust = OptionGroup::create('Crusts', 'Crust', SortOrder::fromInt(10));
    $crust->addOption(Option::withFlatPrice('Thin', Money::zero(), SortOrder::fromInt(10)));
    $crust->addOption(Option::withPricesPerSize('Stuffed', [$large->id => Money::fromTaka('200')], SortOrder::fromInt(20)));
    $toppings = OptionGroup::create('Toppings', 'Extra toppings', SortOrder::fromInt(20));
    $toppings->addOption(Option::withFlatPrice('Corn', Money::fromTaka('50'), SortOrder::fromInt(10), isVeg: true));
    $toppings->addOption(Option::withFlatPrice('Beef', Money::fromTaka('120'), SortOrder::fromInt(20)));
    $olives = Option::withFlatPrice('Olives', Money::fromTaka('60'), SortOrder::fromInt(30));
    $olives->markSoldOut();
    $toppings->addOption($olives);
    $dip = OptionGroup::create('Dips', 'Dip', SortOrder::fromInt(30));
    $dip->addOption(Option::withFlatPrice('Garlic', Money::fromTaka('40'), SortOrder::fromInt(10)));

    $this->crust = $attach($crust, SelectionRules::exactlyOne(), 10);
    $this->toppings = $attach($toppings, SelectionRules::upTo(2, freeCount: 1), 20);
    $this->dip = $attach($dip, SelectionRules::upTo(1), 30);
    [$this->thin, $this->stuffed] = array_map(fn (Option $option): int => (int) $option->id(), $this->groups->find($this->crust)->options());
    [$this->corn, $this->beef, $this->olives] = array_map(fn (Option $option): int => (int) $option->id(), $this->groups->find($this->toppings)->options());
    $this->garlic = (int) $this->groups->find($this->dip)->options()[0]->id();

    $this->component = fn () => Livewire::test(ProductConfigurator::class, ['productId' => $this->product->id, 'variantId' => $this->medium]);
    $this->withCrust = fn () => ($this->component)()->set("single.{$this->crust}", (string) $this->thin);
    // The total of the last render in poisha, null when the choice was refused.
    $this->total = fn (Testable $component): ?int => $component->viewData('line')?->lineTotal->poisha();
});

it('shows sizes, groups, rules and option prices', function (): void {
    ($this->component)()->assertOk()
        ->assertSeeHtmlInOrder(['Size', 'Medium', '৳500', 'Large', '৳900', 'Crust', 'Required, choose 1', 'Thin', 'Free', 'Stuffed', 'Not for this size'])
        ->assertSeeHtmlInOrder(['Extra toppings', 'Choose up to 2, 1 is free', 'Corn', 'Veg', '+৳50', 'Beef', '+৳120', 'Olives', 'Sold out'])
        ->assertSeeHtmlInOrder(['Dip', 'None', 'Garlic', '+৳40']);
});

it('asks for a required group and shows the starting price until then', function (): void {
    ($this->component)()
        ->assertSee('Please choose: Crust.')
        ->assertSee("id=\"problem-{$this->crust}\"", false)
        ->assertSeeHtmlInOrder(['From', '৳500', 'Add to cart']);

    $component = ($this->withCrust)()->assertDontSee('Please choose');

    expect(($this->total)($component))->toBe(50_000);
});

it('updates the price with every choice', function (): void {
    $component = ($this->withCrust)();

    $component->set("multiple.{$this->toppings}", [(string) $this->beef]);
    expect(($this->total)($component))->toBe(50_000); // the only topping is free

    $component->set("multiple.{$this->toppings}", [(string) $this->beef, (string) $this->corn]);
    expect(($this->total)($component))->toBe(62_000); // the cheaper one is free

    $component->set("single.{$this->dip}", (string) $this->garlic)
        ->assertSeeHtmlInOrder(['Your choice', 'Crust: Thin', 'Extra toppings: Corn', 'Free', 'Extra toppings: Beef', '+৳120', 'Dip: Garlic']);
    expect(($this->total)($component))->toBe(66_000);

    $component->set("single.{$this->dip}", '');
    expect(($this->total)($component))->toBe(62_000);
});

it('uses the prices of the chosen size', function (): void {
    $component = ($this->component)()
        ->set('variantId', (string) $this->large)
        ->set("single.{$this->crust}", (string) $this->stuffed);

    expect(($this->total)($component))->toBe(110_000);
});

it('takes away picks that the new size does not offer', function (): void {
    ($this->component)()
        ->set('variantId', (string) $this->large)
        ->set("single.{$this->crust}", (string) $this->stuffed)
        ->set("multiple.{$this->toppings}", [(string) $this->corn])
        ->set('variantId', (string) $this->medium)
        ->assertSet("single.{$this->crust}", '')
        ->assertSet("multiple.{$this->toppings}", [(string) $this->corn])
        ->assertSee('Please choose: Crust.');
});

it('changes the quantity between 1 and 20', function (): void {
    $component = ($this->withCrust)()->call('less')->assertSet('quantity', 1)->call('more')
        ->assertSet('quantity', 2)
        ->assertSee('৳500 each');

    expect(($this->total)($component))->toBe(100_000);

    foreach (range(1, 25) as $click) {
        $component->call('more');
    }

    $component->assertSet('quantity', 20);
});

it('switches the other checkboxes off when the group is full', function (): void {
    $html = ($this->withCrust)()->set("multiple.{$this->toppings}", [(string) $this->corn, (string) $this->beef])->html();

    expect($html)->toMatch("#value=\"{$this->olives}\"[^>]*disabled#")
        ->not->toMatch("#value=\"{$this->corn}\"[^>]*disabled#"); // a pick can be taken back
});

it('refuses picks the page does not offer', function (Closure $choose, string $message): void {
    $component = $choose->call($this, ($this->withCrust)())->assertSee($message);

    expect(($this->total)($component))->toBeNull();
})->with([
    'too many' => [fn ($component) => $component->set("multiple.{$this->toppings}", [$this->corn, $this->beef, $this->olives]), 'choose at most 2'],
    'sold out' => [fn ($component) => $component->set("multiple.{$this->toppings}", [(string) $this->olives]), 'is sold out right now'],
    'an option of another group' => [fn ($component) => $component->set("multiple.{$this->toppings}", [(string) $this->garlic]), 'is not offered here'],
    'not for this size' => [fn ($component) => $component->set("single.{$this->crust}", (string) $this->stuffed), 'is not offered for Medium'],
    'an unknown size' => [fn ($component) => $component->set('variantId', '424242'), 'Choose a size'],
    'no size' => [fn ($component) => $component->set('variantId', 'abc'), 'Choose a valid product and size'],
]);

it('ignores nonsense from the browser', function (): void {
    $component = ($this->withCrust)()
        ->set("multiple.{$this->toppings}", ['abc', ['x'], '-1', '', (string) $this->corn, (string) $this->corn])
        ->set('multiple.424242', ['1'])
        ->set("single.{$this->dip}", ['x'])
        ->assertOk();

    expect(($this->total)($component))->toBe(50_000)
        ->and($component->viewData('line')->options)->toHaveCount(2); // Thin and Corn
});

it('refuses another product and a typed quantity', function (string $property): void {
    expect(fn () => ($this->component)()->set($property, 999))->toThrow(CannotUpdateLockedPropertyException::class);
})->with(['productId', 'quantity']);

it('says so when the item sells out or leaves the menu while the page is open', function (): void {
    $component = ($this->withCrust)();

    $this->product->update(['is_available' => false]);
    $component->call('more')->assertSee('is sold out right now');
    expect(($this->total)($component))->toBeNull();

    $this->product->update(['is_published' => false]);
    $component->call('more')->assertOk()->assertSee('not on the menu any more');
});

it('cannot put anything into a cart yet', function (): void {
    expect(($this->withCrust)()->html())->toMatch('#<button[^>]*disabled[^>]*>\s*Add to cart#');
});

it('gives the configuration of visible products only', function (): void {
    $query = app(ProductConfigurationQuery::class);

    expect($query->forProduct($this->product->id)->variants)->toHaveCount(2)
        ->and($query->forProduct($this->product->id)->groups)->toHaveCount(3)
        ->and($query->forProduct($this->product->id)->variant($this->large)?->label)->toBe('Large')
        ->and($query->forProduct($this->product->id)->variant(424242))->toBeNull()
        ->and($query->forProduct(424242))->toBeNull();

    $this->category->update(['is_active' => false]);
    expect($query->forProduct($this->product->id))->toBeNull();
});

it('needs the same number of queries for many options as for few', function (): void {
    $count = function (): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        ($this->withCrust)();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    };
    $few = $count();
    $toppings = $this->groups->find($this->toppings);

    foreach (range(1, 8) as $number) {
        $toppings->addOption(Option::withFlatPrice("Topping {$number}", Money::fromTaka('10'), SortOrder::fromInt(100 + $number * 10)));
    }

    $this->groups->save($toppings);

    expect($count())->toBe($few);
});
