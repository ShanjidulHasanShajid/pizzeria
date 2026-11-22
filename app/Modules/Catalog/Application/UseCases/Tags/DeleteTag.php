<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Tags;

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Domain\Repositories\TagRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * Moves a tag to the Trash. Never blocked: products keep their link to the tag,
 * the shop simply stops showing it, and restoring the tag brings the badges back.
 */
final class DeleteTag
{
    public function __construct(private readonly TagRepository $tags) {}

    public function handle(CatalogItemInput $input): void
    {
        if ($this->tags->find($input->id) === null) {
            throw EntityNotFound::withId('Tag', $input->id);
        }

        $this->tags->delete($input->id);
    }
}
