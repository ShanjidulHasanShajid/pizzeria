<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\UseCases;

use App\Modules\Cart\Application\DTOs\MergeGuestCartInput;
use App\Modules\Cart\Application\Ports\CartContext;
use App\Modules\Cart\Domain\Exceptions\CartRejected;
use App\Modules\Cart\Domain\Repositories\CartRepository;
use App\Modules\Cart\Domain\ValueObjects\CartOwner;
use App\Modules\Shared\Application\Ports\TransactionManager;

/**
 * A guest logged in: their cart joins the customer's cart. Running it twice does no harm,
 * because the guest cart is gone after the first time.
 */
final class MergeGuestCart
{
    public function __construct(
        private readonly CartContext $context,
        private readonly CartRepository $carts,
        private readonly TransactionManager $transactions,
    ) {}

    /** Returns how many items did not fit into the customer's cart. */
    public function handle(MergeGuestCartInput $input): int
    {
        $guest = $this->context->guest();

        if ($guest === null) {
            return 0;
        }

        $leftOut = $this->carts->find($guest) === null ? 0 : $this->merge($guest, CartOwner::customer($input->userId));
        $this->context->forgetGuest();

        return $leftOut;
    }

    private function merge(CartOwner $guest, CartOwner $customer): int
    {
        $this->carts->createIfMissing($customer);

        return $this->transactions->run(function () use ($guest, $customer): int {
            $this->carts->lock($guest, $customer);
            $guestCart = $this->carts->find($guest);

            if ($guestCart === null) {
                return 0; // another request was faster
            }

            $customerCart = $this->carts->find($customer) ?? throw CartRejected::changedMeanwhile();
            $leftOut = $customerCart->takeOver($guestCart);

            $this->carts->save($customerCart);
            $this->carts->delete($guestCart);

            return $leftOut;
        });
    }
}
