<?php

declare(strict_types=1);

namespace App\Modules\Cart\Infrastructure\Persistence\Repositories;

use App\Modules\Cart\Domain\Entities\Cart;
use App\Modules\Cart\Domain\Repositories\CartRepository;
use App\Modules\Cart\Domain\ValueObjects\CartOwner;
use App\Modules\Cart\Infrastructure\Persistence\Mappers\CartMapper;
use App\Modules\Cart\Infrastructure\Persistence\Models\Cart as CartModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class EloquentCartRepository implements CartRepository
{
    public function __construct(private readonly CartMapper $mapper) {}

    public function find(CartOwner $owner): ?Cart
    {
        $model = $this->of($owner)->with('lines')->first();

        return $model === null ? null : $this->mapper->toEntity($model);
    }

    public function createIfMissing(CartOwner $owner): void
    {
        if ($this->of($owner)->exists()) {
            return;
        }

        // Two requests can get here at the same moment. The unique index lets only one row in,
        // and "or ignore" makes the second insert do nothing instead of failing.
        $now = now();

        CartModel::query()->insertOrIgnore([
            ...$this->mapper->ownerColumns($owner),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function lock(CartOwner ...$owners): void
    {
        foreach ($owners as $owner) {
            $this->of($owner)->lockForUpdate()->value('id');
        }
    }

    public function save(Cart $cart): void
    {
        DB::transaction(function () use ($cart): void {
            $model = CartModel::query()->with('lines')->findOrFail($cart->id());
            $rows = $model->lines->keyBy('id');
            $keptIds = [];

            foreach ($cart->lines() as $line) {
                if ($line->id() !== null) {
                    $keptIds[] = $line->id();
                }
            }

            $model->lines()->whereNotIn('id', $keptIds)->delete();

            foreach ($cart->lines() as $line) {
                $attributes = $this->mapper->lineAttributes($line);
                $row = $rows->get($line->id() ?? 0);

                if ($row === null) {
                    $model->lines()->create($attributes);
                } else {
                    $row->update($attributes); // writes only when something changed
                }
            }

            // The age of the cart is the age of its last change, also when only a line changed.
            $model->touch();
        });
    }

    public function delete(Cart $cart): void
    {
        // cart_lines has cascadeOnDelete: the database removes the lines too.
        CartModel::query()->whereKey($cart->id())->delete();
    }

    /**
     * @return Builder<CartModel>
     */
    private function of(CartOwner $owner): Builder
    {
        return CartModel::query()->where($this->mapper->ownerColumns($owner));
    }
}
