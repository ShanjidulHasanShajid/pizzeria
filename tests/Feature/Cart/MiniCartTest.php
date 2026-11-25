<?php

declare(strict_types=1);

use App\Modules\Cart\Application\DTOs\PricedCart;
use App\Modules\Cart\Application\UseCases\GetPricedCart;
use App\Modules\Cart\Presentation\Livewire\CartCounter;
use App\Modules\Cart\Presentation\Livewire\MiniCart;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductVariant as ProductVariantModel;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->category = CategoryModel::factory()->create(['slug' => 'drinks']);
    $this->cola = ProductModel::factory()->for($this->category)
        ->has(ProductVariantModel::factory()->asDefault()->state(['label' => 'Can', 'price' => 6_000]), 'variants')
        ->create(['name' => 'Cola', 'slug' => 'cola']);
    $this->can = $this->cola->variants()->firstOrFail();
    $this->cart = fn (): PricedCart => app(GetPricedCart::class)->handle();
    $this->click = fn () => Livewire::test(MiniCart::class)->dispatch('add-to-cart', productId: $this->cola->id, variantId: $this->can->id);
});

describe('mini cart', function (): void {
    it('is closed and empty on every page, and asks the database nothing', function (): void {
        DB::enableQueryLog();

        Livewire::test(MiniCart::class)->assertSet('isOpen', false)->assertDontSee('Added to your cart.');

        expect(DB::getQueryLog())->toBe([]);
    });

    it('adds the product of a card, opens and tells the counter', function (): void {
        ($this->click)()
            ->assertSet('isOpen', true)
            ->assertSee('Added to your cart.')
            ->assertSeeHtmlInOrder(['Cola', 'Can', '৳60', 'Subtotal', '৳60', 'View cart'])
            ->assertDispatched('cart-changed');

        expect(($this->cart)()->itemCount())->toBe(1);
    });

    it('opens when the product page added something', function (): void {
        ($this->click)();

        Livewire::test(MiniCart::class)->dispatch('cart-item-added')
            ->assertSet('isOpen', true)
            ->assertSee('Added to your cart.')
            ->assertSee('Cola');

        expect(($this->cart)()->itemCount())->toBe(1); // opening adds nothing
    });

    it('opens with the reason when the item is refused', function (): void {
        $this->cola->update(['is_available' => false]);

        ($this->click)()
            ->assertSet('isOpen', true)
            ->assertSee('is sold out right now')
            ->assertDontSee('Added to your cart.')
            ->assertNotDispatched('cart-changed');

        expect(($this->cart)()->isEmpty())->toBeTrue();
    });

    it('refuses ids that do not belong together', function (): void {
        $other = ProductModel::factory()->for($this->category)->create();

        Livewire::test(MiniCart::class)
            ->dispatch('add-to-cart', productId: $other->id, variantId: $this->can->id)
            ->assertSee('Choose a size')
            ->assertNotDispatched('cart-changed');
    });

    it('refuses a combo, whatever the browser sends', function (): void {
        $combo = ProductModel::factory()->combo()->for($this->category)->create(['name' => 'Feast']);

        Livewire::test(MiniCart::class)
            ->dispatch('add-to-cart', productId: $combo->id, variantId: $combo->variants()->firstOrFail()->id)
            ->assertSee('cannot be ordered like this');
    });
});

describe('cart counter', function (): void {
    it('counts items and reads again after a change', function (): void {
        $counter = Livewire::test(CartCounter::class)->assertSeeHtml('aria-label="Cart, empty"');

        ($this->click)();
        $counter->dispatch('cart-changed')->assertSeeHtml('aria-label="Cart, 1 item"');

        ($this->click)();
        $counter->dispatch('cart-changed')->assertSeeHtml('aria-label="Cart, 2 items"');
    });

    it('has one layout for the header and one for the bottom bar', function (): void {
        Livewire::test(CartCounter::class, ['placement' => 'bar'])->assertSeeInOrder(['Cart']);
        Livewire::test(CartCounter::class, ['placement' => 'header'])->assertSeeHtml(route('cart.show'));
    });

    it('needs no query for a visitor without a cart and one for the others', function (): void {
        DB::enableQueryLog();
        Livewire::test(CartCounter::class);
        expect(DB::getQueryLog())->toBe([]);

        ($this->click)();
        DB::flushQueryLog();
        Livewire::test(CartCounter::class);
        expect(DB::getQueryLog())->toHaveCount(1);
    });

    it('counts only the cart of this visitor', function (): void {
        ($this->click)();
        $this->actingAs(User::factory()->create());

        Livewire::test(CartCounter::class)->assertSeeHtml('aria-label="Cart, empty"');
    });
});

it('puts both counters and the closed mini cart on every shop page', function (string $uri): void {
    $html = (string) $this->get($uri)->assertOk()->assertSeeLivewire(MiniCart::class)->getContent();

    expect(substr_count($html, 'aria-label="Cart, empty"'))->toBe(2);
})->with(['/', '/menu', '/product/cola', '/cart', '/faq', '/login']);

it('remembers the cart of a guest from page to page', function (): void {
    ($this->click)();

    $this->get('/menu')->assertSee('aria-label="Cart, 1 item"', false);
    $this->get('/cart')->assertSee('Cola')->assertSee('Proceed to checkout');
});

it('offers "Add to cart" on a card only when there is nothing to choose', function (): void {
    ProductModel::factory()->for($this->category)
        ->has(ProductVariantModel::factory()->count(2), 'variants')
        ->create(['name' => 'Lemonade', 'slug' => 'lemonade']);

    $html = (string) $this->get('/menu/drinks')->assertOk()->getContent();

    expect($html)->toContain("productId: {$this->cola->id}, variantId: {$this->can->id}")
        ->and(substr_count($html, 'productId:'))->toBe(1) // not on the card of Lemonade
        ->and($html)->toContain('Choose options');
});

it('does not repeat the mini cart in a page of cards', function (): void {
    ProductModel::factory()->count(6)->for($this->category)->create();

    $html = (string) $this->get('/menu/drinks')->getContent();

    expect(substr_count($html, 'wire:snapshot="'))->toBe(3); // two counters and the mini cart
});
