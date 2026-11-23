<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

final readonly class ReorderOptionGroupsInput
{
    /**
     * @param  list<int>  $ids  the group ids in their new order
     */
    public function __construct(public array $ids) {}
}
