<?php

declare(strict_types=1);

use App\Modules\Cart\Application\DTOs\AddStandardItemInput;
use App\Modules\Cart\Application\DTOs\MergeGuestCartInput;
use App\Modules\Cart\Application\DTOs\PricedCart;
use App\Modules\Cart\Application\UseCases\AddStandardItem;
use App\Modules\Cart\Application\UseCases\GetPricedCart;
use App\Modules\Cart\Application\UseCases\MergeGuestCart;
use App\Modules\Cart\Domain\Enums\CartLineStatus;
use App\Modules\Cart\Domain\Exceptions\CartRejected;
use App\Modules\Cart\Infrastructure\Persistence\Models\Cart as CartModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductVariant as ProductVariantModel;
use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Entities\ProductOptionGroup;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Customization\Domain\Repositories\ProductOptionGroupRepository;
use App\Modules\Customization\Domain\ValueObjects\SelectionRules;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Illuminate\Support\Facades\DB;

/*
| The whole way with the real catalog, the real pricing engine and the real database.
| Margherita ৳450; required crust: Stuffed +৳150.
*/
beforeEach(function (): void {
    $this->category = CategoryModel::factory()->create();
    $this->item = fn (string $name, int $poisha): ProductModel => ProductModel::factory()->for($this->category)
        ->has(ProductVariantModel::factory()->asDefault()->state(['label' => 'Regular', 'price' => $poisha]), 'variants')
        ->create(['name' => $name]);

    $this->pizza = ($this->item)('Margherita', 45_000);
    $groups = app(OptionGroupRepository::class);
    $crust = OptionGroup::create('Crusts', 'Crust', SortOrder::first());
    $crust->addOption(Option::withFlatPrice('Stuffed', Money::fromTaka('150'), SortOrder::first()));
    $this->crustId = $groups->save($crust);
    $this->stuffedId = $groups->find($this->crustId)->options()[0]->id();
    app(ProductOptionGroupRepository::class)->save(
        ProductOptionGroup::attach($this->pizza->id, $this->crustId, SelectionRules::exactlyOne(), SortOrder::first()),
    );

    $this->add = fn (ProductModel $product, array $choices = [], int $quantity = 1) => app(AddStandardItem::class)->handle(
        new AddStandardItemInput($product->id, $product->variants()->firstOrFail()->id, $choices, null, $quantity),
    );
    $this->addPizza = fn (int $quantity = 1) => ($this->add)($this->pizza, [$this->crustId => [$this->stuffedId]], $quantity);
    $this->cart = fn (): PricedCart => app(GetPricedCart::class)->handle();
});

it('adds a pizza with its options at the price of the server', function (): void {
    ($this->addPizza)(2);

    $line = ($this->cart)()->lines[0];

    expect($line->productName)->toBe('Margherita')
        ->and($line->productSlug)->toBe($this->pizza->slug)
        ->and($line->variantLabel)->toBe('Regular')
        ->and($line->options[0]->name)->toBe('Stuffed')
        ->and($line->options[0]->charged->poisha())->toBe(15_000)
        ->and($line->unitPrice->poisha())->toBe(60_000)
        ->and($line->lineTotal->poisha())->toBe(120_000)
        ->and($line->status)->toBe(CartLineStatus::Ok);
});

it('refuses a pizza without its required crust', function (): void {
    ($this->add)($this->pizza);
})->throws(CartRejected::class, 'Please choose: Crust.');

it('refuses the size of another product', function (): void {
    $other = ($this->item)('Cola', 6_000);

    app(AddStandardItem::class)->handle(new AddStandardItemInput($other->id, $this->pizza->variants()->firstOrFail()->id));
})->throws(CartRejected::class, 'Choose a size');

it('refuses a combo', function (): void {
    ($this->add)(ProductModel::factory()->combo()->for($this->category)->create());
})->throws(CartRejected::class, 'cannot be ordered like this');

it('shows a price that the admin changed', function (): void {
    ($this->addPizza)();
    $this->pizza->variants()->firstOrFail()->update(['price' => 50_000]);

    $line = ($this->cart)()->lines[0];

    expect($line->status)->toBe(CartLineStatus::PriceChanged)
        ->and($line->unitPrice->poisha())->toBe(65_000)
        ->and($line->lastUnitPrice->poisha())->toBe(60_000);
});

it('marks a line as unavailable', function (Closure $happens): void {
    ($this->addPizza)();
    $happens($this->pizza);

    $cart = ($this->cart)();

    expect($cart->lines[0]->status)->toBe(CartLineStatus::Unavailable)
        ->and($cart->lines[0]->productName)->toBe('Margherita')
        ->and($cart->lines[0]->problem)->not->toBeEmpty()
        ->and($cart->subtotal->isZero())->toBeTrue()
        ->and($cart->canCheckOut())->toBeFalse();
})->with([
    'sold out' => [fn (ProductModel $product) => $product->update(['is_available' => false])],
    'size sold out' => [fn (ProductModel $product) => $product->variants()->firstOrFail()->update(['is_available' => false])],
    'unpublished' => [fn (ProductModel $product) => $product->update(['is_published' => false])],
    'in the Trash' => [fn (ProductModel $product) => $product->delete()],
    'size removed' => [fn (ProductModel $product) => $product->variants()->firstOrFail()->delete()],
]);

it('links to the product only while customers can open its page', function (): void {
    ($this->addPizza)();
    $this->pizza->update(['is_published' => false]);

    expect(($this->cart)()->lines[0]->productSlug)->toBeNull();
});

it('moves the guest cart to the customer', function (): void {
    ($this->addPizza)(2);
    $user = User::factory()->create();
    $this->actingAs($user);

    app(MergeGuestCart::class)->handle(new MergeGuestCartInput($user->id));

    expect(($this->cart)()->itemCount())->toBe(2)
        ->and(CartModel::query()->count())->toBe(1)
        ->and(CartModel::query()->firstOrFail()->user_id)->toBe($user->id);
});

it('needs as many queries for ten lines as for one', function (): void {
    $countQueries = function (): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        ($this->cart)();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    };

    ($this->addPizza)();
    $forOne = $countQueries();

    foreach (range(1, 9) as $number) {
        ($this->add)(($this->item)("Drink {$number}", 6_000));
    }

    expect(($this->cart)()->lines)->toHaveCount(10)
        ->and($countQueries())->toBe($forOne);
});
