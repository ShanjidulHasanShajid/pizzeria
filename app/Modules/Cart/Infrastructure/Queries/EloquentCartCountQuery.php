<?php

declare(strict_types=1);

namespace App\Modules\Cart\Infrastructure\Queries;

use App\Modules\Cart\Application\Ports\CartContext;
use App\Modules\Cart\Application\Queries\CartCountQuery;
use App\Modules\Cart\Infrastructure\Persistence\Mappers\CartMapper;
use App\Modules\Cart\Infrastructure\Persistence\Models\Cart as CartModel;
use App\Modules\Cart\Infrastructure\Persistence\Models\CartLine as CartLineModel;

final class EloquentCartCountQuery implements CartCountQuery
{
    public function __construct(
        private readonly CartContext $context,
        private readonly CartMapper $mapper,
    ) {}

    public function forCurrentVisitor(): int
    {
        $owner = $this->context->current();

        if ($owner === null) {
            return 0; // most visitors: no query at all
        }

        return (int) CartLineModel::query()
            ->whereIn('cart_id', CartModel::query()->select('id')->where($this->mapper->ownerColumns($owner)))
            ->sum('quantity');
    }
}
