<?php

declare(strict_types=1);

namespace App\Modules\Cart\Presentation\Livewire;

use App\Modules\Cart\Application\Queries\CartCountQuery;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The cart link with the number of items (11.14). Every change of the cart sends the event
 * "cart-changed", and the number is read again.
 * Used as <livewire:cart.cart-counter placement="header" /> and placement="bar".
 */
final class CartCounter extends Component
{
    #[Locked]
    public string $placement = 'header';

    private CartCountQuery $query;

    public function boot(CartCountQuery $query): void
    {
        $this->query = $query;
    }

    /** Nothing to do: after an event Livewire renders again. */
    #[On('cart-changed')]
    public function refresh(): void {}

    public function render(): View
    {
        return view('cart::livewire.cart-counter', ['count' => $this->query->forCurrentVisitor()]);
    }
}
