<?php

declare(strict_types=1);

namespace App\Modules\Cart\Presentation\Support;

use App\Modules\Cart\Application\DTOs\PricedCart;
use App\Modules\Shared\Application\Ports\FileStorage;

/**
 * The address of the picture of every line.
 */
final class CartImages
{
    public function __construct(private readonly FileStorage $files) {}

    /**
     * @return array<int, string> line id => address
     */
    public function forCart(PricedCart $cart): array
    {
        $addresses = [];

        foreach ($cart->lines as $line) {
            $addresses[$line->lineId] = $line->imagePath === null
                ? asset('images/placeholder.svg')
                : $this->files->url($line->imagePath);
        }

        return $addresses;
    }
}
