<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\UseCases;

use App\Modules\Cart\Application\DTOs\RemoveLineInput;
use App\Modules\Cart\Application\Services\CartEditor;
use App\Modules\Cart\Domain\Entities\Cart;
use App\Modules\Cart\Domain\Exceptions\CartRejected;

final class RemoveLine
{
    public function __construct(private readonly CartEditor $editor) {}

    public function handle(RemoveLineInput $input): void
    {
        if (! $this->editor->edit(fn (Cart $cart) => $cart->removeLine($input->lineId))) {
            throw CartRejected::lineNotFound();
        }
    }
}
