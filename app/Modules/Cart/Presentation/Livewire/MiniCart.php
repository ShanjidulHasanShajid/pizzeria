<?php

declare(strict_types=1);

namespace App\Modules\Cart\Presentation\Livewire;

use App\Modules\Cart\Application\DTOs\AddStandardItemInput;
use App\Modules\Cart\Application\DTOs\PricedCart;
use App\Modules\Cart\Application\UseCases\AddStandardItem;
use App\Modules\Cart\Application\UseCases\GetPricedCart;
use App\Modules\Cart\Presentation\Support\CartImages;
use App\Modules\Shared\Domain\Exceptions\DomainException;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The drawer that opens after something was added (11.17). It is on every shop page, and while it
 * is closed it asks the database nothing.
 * Product cards send the event "add-to-cart" (so a page with 60 cards needs no 60 components);
 * the product page adds by itself and sends "cart-item-added".
 */
final class MiniCart extends Component
{
    /** The browser may close it; it opens only after something was added or refused. */
    public bool $isOpen = false;

    private bool $wasAdded = false;

    private ?string $problem = null;

    private GetPricedCart $getCart;

    private AddStandardItem $addItem;

    private CartImages $images;

    public function boot(GetPricedCart $getCart, AddStandardItem $addItem, CartImages $images): void
    {
        $this->getCart = $getCart;
        $this->addItem = $addItem;
        $this->images = $images;
    }

    /** The ids come from the browser; the use case checks them and takes the price from the database. */
    #[On('add-to-cart')]
    public function add(int $productId, int $variantId): void
    {
        $this->isOpen = true;

        try {
            $this->addItem->handle(new AddStandardItemInput($productId, $variantId));
            $this->wasAdded = true;
            $this->dispatch('cart-changed');
        } catch (DomainException $refused) {
            $this->problem = $refused->getMessage();
        }
    }

    #[On('cart-item-added')]
    public function open(): void
    {
        $this->isOpen = true;
        $this->wasAdded = true;
    }

    public function render(): View
    {
        $cart = $this->isOpen ? $this->getCart->handle() : PricedCart::empty();

        return view('cart::livewire.mini-cart', [
            'cart' => $cart,
            'images' => $this->images->forCart($cart),
            'wasAdded' => $this->wasAdded,
            'problem' => $this->problem,
        ]);
    }
}
