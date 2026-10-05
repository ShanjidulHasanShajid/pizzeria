<?php

declare(strict_types=1);

namespace App\Modules\Shared\Presentation\Support;

use App\Modules\Shared\Domain\ValueObjects\Money;

/**
 * Shows poisha the way customers read prices: ৳1,250 or ৳1,250.50.
 * Accepts a Money object or a plain integer of poisha (used by read DTOs).
 */
final class MoneyFormatter
{
    public function format(Money|int $amount): string
    {
        $poisha = $amount instanceof Money ? $amount->poisha() : $amount;
        $sign = $poisha < 0 ? '-' : '';
        $poisha = abs($poisha);

        $text = $sign.'৳'.number_format(intdiv($poisha, 100));
        $remainder = $poisha % 100;

        return $remainder === 0 ? $text : $text.sprintf('.%02d', $remainder);
    }
}
