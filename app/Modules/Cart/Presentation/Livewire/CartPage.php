<?php

declare(strict_types=1);

namespace App\Modules\Cart\Presentation\Livewire;

use App\Modules\Cart\Application\DTOs\ChangeLineNoteInput;
use App\Modules\Cart\Application\DTOs\ChangeLineQuantityInput;
use App\Modules\Cart\Application\DTOs\PricedCart;
use App\Modules\Cart\Application\DTOs\RemoveLineInput;
use App\Modules\Cart\Application\UseCases\AcceptCurrentPrices;
use App\Modules\Cart\Application\UseCases\ChangeLineNote;
use App\Modules\Cart\Application\UseCases\ChangeLineQuantity;
use App\Modules\Cart\Application\UseCases\ClearCart;
use App\Modules\Cart\Application\UseCases\GetPricedCart;
use App\Modules\Cart\Application\UseCases\RemoveLine;
use App\Modules\Cart\Presentation\Support\CartImages;
use App\Modules\Shared\Domain\Exceptions\DomainException;
use Closure;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * The cart page (11.18 to 11.20). Every action calls one use case; the prices are read again
 * after it. The line ids come from the browser, but a use case finds only lines of this visitor.
 */
final class CartPage extends Component
{
    /** @var array<int, mixed> line id => the note as it stands in the field */
    public array $notes = [];

    private ?string $problem = null;

    private ?string $status = null;

    private GetPricedCart $getCart;

    private CartImages $images;

    public function boot(GetPricedCart $getCart, CartImages $images): void
    {
        $this->getCart = $getCart;
        $this->images = $images;
    }

    /** The wanted quantity and not "one more": a double click asks for the same thing twice. */
    public function setQuantity(ChangeLineQuantity $changeQuantity, int $lineId, int $quantity): void
    {
        $this->attempt(fn () => $changeQuantity->handle(new ChangeLineQuantityInput($lineId, $quantity)));
    }

    public function saveNote(ChangeLineNote $changeNote, int $lineId): void
    {
        $note = $this->notes[$lineId] ?? null;

        if ($this->attempt(fn () => $changeNote->handle(new ChangeLineNoteInput($lineId, is_string($note) ? $note : null)))) {
            unset($this->notes[$lineId]); // render() fills in what was really saved
            $this->status = 'Note saved.';
        }
    }

    public function remove(RemoveLine $removeLine, int $lineId): void
    {
        $this->attempt(fn () => $removeLine->handle(new RemoveLineInput($lineId)));
    }

    public function clear(ClearCart $clearCart): void
    {
        $this->attempt(fn () => $clearCart->handle());
    }

    public function acceptPrices(AcceptCurrentPrices $acceptPrices): void
    {
        $this->attempt(fn () => $acceptPrices->handle());
    }

    public function render(): View
    {
        $cart = $this->getCart->handle();
        $this->fillNotes($cart);

        return view('cart::livewire.cart-page', [
            'cart' => $cart,
            'images' => $this->images->forCart($cart),
            'problem' => $this->problem,
            'status' => $this->status,
        ]);
    }

    private function attempt(Closure $action): bool
    {
        try {
            $action();
            $this->dispatch('cart-changed');

            return true;
        } catch (DomainException $refused) {
            $this->problem = $refused->getMessage();

            return false;
        }
    }

    /** Keeps what somebody is typing, adds the notes of new lines, forgets those of lines that are gone. */
    private function fillNotes(PricedCart $cart): void
    {
        $notes = [];

        foreach ($cart->lines as $line) {
            $typed = $this->notes[$line->lineId] ?? null;
            $notes[$line->lineId] = is_string($typed) ? $typed : ($line->note ?? '');
        }

        $this->notes = $notes;
    }
}
