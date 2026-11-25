<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\UseCases;

use App\Modules\Cart\Application\DTOs\ChangeLineNoteInput;
use App\Modules\Cart\Application\Services\CartEditor;
use App\Modules\Cart\Domain\Entities\Cart;
use App\Modules\Cart\Domain\Exceptions\CartRejected;
use App\Modules\Cart\Domain\ValueObjects\KitchenNote;

/**
 * Changes or removes the note for the kitchen of one line.
 */
final class ChangeLineNote
{
    public function __construct(private readonly CartEditor $editor) {}

    public function handle(ChangeLineNoteInput $input): void
    {
        $note = KitchenNote::of($input->note);

        if (! $this->editor->edit(fn (Cart $cart) => $cart->changeNote($input->lineId, $note))) {
            throw CartRejected::lineNotFound();
        }
    }
}
