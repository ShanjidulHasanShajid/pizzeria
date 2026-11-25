<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\Ports;

use App\Modules\Ordering\Domain\ValueObjects\OrderNumber;

interface OrderNumberGenerator
{
    /** A random number that no order has yet. Two numbers in a row have nothing in common. */
    public function next(): OrderNumber;
}
