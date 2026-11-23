<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

final readonly class BulkAttachResult
{
    /**
     * @param  int  $attached  products that got the group now
     * @param  int  $alreadyAttached  products that had it; they kept their own rules
     */
    public function __construct(
        public int $attached,
        public int $alreadyAttached,
    ) {}
}
