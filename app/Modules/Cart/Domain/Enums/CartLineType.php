<?php

declare(strict_types=1);

namespace App\Modules\Cart\Domain\Enums;

/**
 * What kind of thing a cart line holds. Combos (Phase 15) and custom pizzas (Phase 16)
 * get their own case, because they remember other choices.
 */
enum CartLineType: string
{
    case Standard = 'standard';
}
