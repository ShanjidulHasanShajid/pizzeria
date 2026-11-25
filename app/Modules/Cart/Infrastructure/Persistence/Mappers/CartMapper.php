<?php

declare(strict_types=1);

namespace App\Modules\Cart\Infrastructure\Persistence\Mappers;

use App\Modules\Cart\Domain\Entities\Cart;
use App\Modules\Cart\Domain\Entities\CartLine;
use App\Modules\Cart\Domain\ValueObjects\CartOwner;
use App\Modules\Cart\Domain\ValueObjects\ChosenOptions;
use App\Modules\Cart\Domain\ValueObjects\KitchenNote;
use App\Modules\Cart\Infrastructure\Persistence\Models\Cart as CartModel;
use App\Modules\Cart\Infrastructure\Persistence\Models\CartLine as CartLineModel;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\Quantity;

/**
 * Translates between the carts and cart_lines rows and the Cart aggregate.
 */
final class CartMapper
{
    /**
     * The model must come with lines loaded.
     */
    public function toEntity(CartModel $model): Cart
    {
        return Cart::reconstitute(
            $model->id,
            $model->user_id === null ? CartOwner::guest((string) $model->guest_token) : CartOwner::customer($model->user_id),
            array_values($model->lines->map(fn (CartLineModel $line): CartLine => $this->toLine($line))->all()),
        );
    }

    /**
     * The column that finds the cart of this owner.
     *
     * @return array<string, int|string|null>
     */
    public function ownerColumns(CartOwner $owner): array
    {
        return $owner->isGuest() ? ['guest_token' => $owner->guestToken()] : ['user_id' => $owner->userId()];
    }

    /**
     * The cart_lines columns for fill().
     *
     * @return array<string, mixed>
     */
    public function lineAttributes(CartLine $line): array
    {
        return [
            'type' => $line->type(),
            'product_id' => $line->productId(),
            'variant_id' => $line->variantId(),
            'selections' => $line->options()->toArray(),
            'note' => $line->note()->text(),
            'quantity' => $line->quantity()->value(),
            'last_unit_price' => $line->lastUnitPrice()->poisha(),
        ];
    }

    private function toLine(CartLineModel $model): CartLine
    {
        return CartLine::reconstitute(
            $model->id,
            $model->type,
            $model->product_id,
            $model->variant_id,
            ChosenOptions::of($model->selections),
            KitchenNote::of($model->note),
            Quantity::of($model->quantity),
            Money::fromPoisha($model->last_unit_price),
        );
    }
}
