<?php

declare(strict_types=1);

namespace App\Modules\Cart\Presentation\Console;

use App\Modules\Cart\Application\UseCases\DeleteOldGuestCarts;
use Illuminate\Console\Command;

/**
 * Runs every night (routes/console.php). It can also be started by hand.
 */
final class DeleteOldGuestCartsCommand extends Command
{
    protected $signature = 'cart:delete-old-guest-carts';

    protected $description = 'Delete guest carts that were not changed for 5 days';

    public function handle(DeleteOldGuestCarts $deleteOldGuestCarts): int
    {
        $this->info(sprintf('Old guest carts deleted: %d', $deleteOldGuestCarts->handle()));

        return self::SUCCESS;
    }
}
