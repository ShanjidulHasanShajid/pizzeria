<?php

declare(strict_types=1);

use App\Modules\Cart\Application\DTOs\AddStandardItemInput;
use App\Modules\Cart\Application\DTOs\PricedCart;
use App\Modules\Cart\Application\UseCases\AddStandardItem;
use App\Modules\Cart\Application\UseCases\GetPricedCart;
use App\Modules\Cart\Presentation\Livewire\CartPage;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductVariant as ProductVariantModel;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use Livewire\Livewire;

/*
| Cola ৳60 and Brownie ৳220, without options.
*/
beforeEach(function (): void {
    $category = CategoryModel::factory()->create();
    $item = fn (string $name, string $slug, int $poisha): ProductModel => ProductModel::factory()->for($category)
        ->has(ProductVariantModel::factory()->asDefault()->state(['label' => 'Regular', 'price' => $poisha]), 'variants')
        ->create(['name' => $name, 'slug' => $slug]);
    $this->cola = $item('Cola', 'cola', 6_000);
    $this->brownie = $item('Brownie', 'brownie', 22_000);

    $this->add = fn (ProductModel $product, int $quantity = 1, ?string $note = null) => app(AddStandardItem::class)->handle(
        new AddStandardItemInput($product->id, $product->variants()->firstOrFail()->id, [], $note, $quantity),
    );
    $this->cart = fn (): PricedCart => app(GetPricedCart::class)->handle();
    $this->lineId = fn (int $position = 0): int => ($this->cart)()->lines[$position]->lineId;
    // Livewire writes comments (<!--[if BLOCK]> ...) between the tag and the text.
    $this->blockedCheckout = '#<button[^>]* disabled="disabled"[^>]*>(?:\s|<!--.*?-->)*Proceed to checkout#';
});

it('is a page of its own that search engines leave alone', function (): void {
    $this->get('/cart')->assertOk()
        ->assertSee('Your cart')
        ->assertSee('noindex')
        ->assertSeeLivewire(CartPage::class);
});

it('invites to the menu when the cart is empty', function (): void {
    Livewire::test(CartPage::class)
        ->assertSee('Your cart is empty')
        ->assertSee(route('menu.index'))
        ->assertDontSee('Proceed to checkout');
});

it('shows every line with its note, the totals and the way to the checkout', function (): void {
    ($this->add)($this->cola, 3, 'no ice');
    ($this->add)($this->brownie);

    Livewire::test(CartPage::class)
        ->assertSeeHtmlInOrder(['Cola', '৳180', 'Regular', '৳60', 'each', 'Brownie', '৳220', 'Subtotal', '৳400', 'Proceed to checkout'])
        ->assertSee(route('products.show', 'cola'))
        ->assertSee(route('checkout.show'))
        ->assertSet('notes.'.($this->lineId)(), 'no ice');
});

it('prints a note as text, never as HTML', function (): void {
    ($this->add)($this->cola, 1, '<script>alert(1)</script>');
    $this->cola->update(['is_available' => false]); // then the note is printed instead of in a field

    Livewire::test(CartPage::class)
        ->assertSee('<script>alert(1)</script>') // escaped
        ->assertDontSeeHtml('<script>alert(1)</script>');
});

it('changes the quantity and tells the counter', function (): void {
    ($this->add)($this->cola);

    Livewire::test(CartPage::class)
        ->call('setQuantity', ($this->lineId)(), 4)
        ->assertDispatched('cart-changed')
        ->assertSeeHtml('৳240');

    expect(($this->cart)()->itemCount())->toBe(4);
});

it('refuses a quantity outside 1 to 20 with a message', function (int $quantity): void {
    ($this->add)($this->cola, 5);

    Livewire::test(CartPage::class)
        ->call('setQuantity', ($this->lineId)(), $quantity)
        ->assertSee('Quantity must be between 1 and 20.')
        ->assertNotDispatched('cart-changed');

    expect(($this->cart)()->itemCount())->toBe(5);
})->with([0, 21, -3]);

it('saves a note', function (): void {
    ($this->add)($this->cola);
    $lineId = ($this->lineId)();

    Livewire::test(CartPage::class)
        ->set("notes.{$lineId}", '  no ice ')
        ->call('saveNote', $lineId)
        ->assertSee('Note saved.')
        ->assertSet("notes.{$lineId}", 'no ice');

    expect(($this->cart)()->lines[0]->note)->toBe('no ice');
});

it('keeps a note that is too long in the field and says why', function (): void {
    ($this->add)($this->cola);
    $lineId = ($this->lineId)();
    $long = str_repeat('a', 201);

    Livewire::test(CartPage::class)
        ->set("notes.{$lineId}", $long)
        ->call('saveNote', $lineId)
        ->assertSee('at most 200 characters')
        ->assertSet("notes.{$lineId}", $long);

    expect(($this->cart)()->lines[0]->note)->toBeNull();
});

it('does not lose what is typed in one note when something else changes', function (): void {
    ($this->add)($this->cola);
    ($this->add)($this->brownie);
    $typing = ($this->lineId)();

    Livewire::test(CartPage::class)
        ->set("notes.{$typing}", 'half a sentence')
        ->call('setQuantity', ($this->lineId)(1), 2)
        ->assertSet("notes.{$typing}", 'half a sentence');
});

it('turns two lines into one when their notes become the same', function (): void {
    ($this->add)($this->cola, 2);
    ($this->add)($this->cola, 1, 'no ice');
    $second = ($this->lineId)(1);

    Livewire::test(CartPage::class)->set("notes.{$second}", '')->call('saveNote', $second);

    expect(($this->cart)()->lines)->toHaveCount(1)
        ->and(($this->cart)()->itemCount())->toBe(3);
});

it('removes a line, and the last one leaves an empty cart', function (): void {
    ($this->add)($this->cola);
    ($this->add)($this->brownie);

    $page = Livewire::test(CartPage::class)->call('remove', ($this->lineId)())
        ->assertDispatched('cart-changed')
        ->assertDontSee('Cola')
        ->assertSee('Brownie');
    $page->call('remove', ($this->lineId)())->assertSee('Your cart is empty');
});

it('empties the cart', function (): void {
    ($this->add)($this->cola);

    Livewire::test(CartPage::class)->call('clear')->assertDispatched('cart-changed')->assertSee('Your cart is empty');
});

it('says so when a line was already removed in another tab', function (): void {
    ($this->add)($this->cola);
    $lineId = ($this->lineId)();
    $page = Livewire::test(CartPage::class)->call('remove', $lineId);

    $page->call('setQuantity', $lineId, 2)->assertOk()->assertSee('not in your cart any more');
});

it('shows a changed price until the customer accepts it', function (): void {
    ($this->add)($this->cola);
    $this->cola->variants()->firstOrFail()->update(['price' => 7_000]);

    $page = Livewire::test(CartPage::class)
        ->assertSee('Some prices have changed')
        ->assertSeeHtmlInOrder(['The price changed from', '৳60', 'to', '৳70'])
        ->assertSee(route('checkout.show')); // a changed price does not block

    $page->call('acceptPrices')->assertDontSee('Some prices have changed')->assertDontSee('The price changed');
});

it('blocks the checkout while a line is unavailable', function (): void {
    ($this->add)($this->cola);
    ($this->add)($this->brownie);
    $this->cola->update(['is_available' => false]);

    $page = Livewire::test(CartPage::class)
        ->assertSee('Some items cannot be ordered right now')
        ->assertSee('is sold out right now')
        ->assertSeeHtml('৳220')
        ->assertDontSee(route('checkout.show'));
    expect($page->html())->toMatch($this->blockedCheckout);

    $page->call('remove', ($this->lineId)())
        ->assertDontSee('Some items cannot be ordered right now')
        ->assertSee(route('checkout.show'));
});

it('names a deleted product without a link to its page', function (): void {
    ($this->add)($this->cola);
    $this->cola->delete();

    Livewire::test(CartPage::class)
        ->assertSee('Cola')
        ->assertSee('no longer on the menu')
        ->assertDontSee('/product/cola');
});

it('never touches the cart of somebody else', function (): void {
    ($this->add)($this->cola, 2);
    $guestLine = ($this->lineId)();
    $this->actingAs(User::factory()->create());

    $page = Livewire::test(CartPage::class)->assertSee('Your cart is empty');
    $page->call('setQuantity', $guestLine, 9)->assertSee('not in your cart any more');
    $page->call('remove', $guestLine)->assertSee('not in your cart any more');

    auth()->logout();
    expect(($this->cart)()->itemCount())->toBe(2);
});

it('ignores nonsense in the note fields', function (): void {
    ($this->add)($this->cola);
    $lineId = ($this->lineId)();

    Livewire::test(CartPage::class)
        ->set('notes', [$lineId => ['x'], 424242 => 'nobody'])
        ->call('saveNote', $lineId)
        ->assertOk()
        ->assertSet('notes', [$lineId => '']);
});
