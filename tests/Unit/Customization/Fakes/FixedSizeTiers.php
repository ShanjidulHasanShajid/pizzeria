<?php

declare(strict_types=1);

namespace Tests\Unit\Customization\Fakes;

use App\Modules\Customization\Application\Ports\SizeTiers;

final class FixedSizeTiers implements SizeTiers
{
    /**
     * @param  list<int>  $ids
     */
    public function __construct(private readonly array $ids) {}

    public function ids(): array
    {
        return $this->ids;
    }
}
