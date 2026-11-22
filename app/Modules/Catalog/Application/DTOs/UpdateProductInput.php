<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

final readonly class UpdateProductInput
{
    public function __construct(
        public int $id,
        public ProductFormInput $form,
    ) {}
}
