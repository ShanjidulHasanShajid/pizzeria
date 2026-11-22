<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Tags;

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Domain\Repositories\TagRepository;

final class RestoreTag
{
    public function __construct(private readonly TagRepository $tags) {}

    public function handle(CatalogItemInput $input): void
    {
        $this->tags->restore($input->id);
    }
}
