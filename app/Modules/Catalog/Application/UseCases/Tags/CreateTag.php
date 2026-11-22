<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Tags;

use App\Modules\Catalog\Application\DTOs\CreateTagInput;
use App\Modules\Catalog\Domain\Entities\Tag;
use App\Modules\Catalog\Domain\Repositories\TagRepository;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\Services\SlugGenerator;
use App\Modules\Shared\Domain\ValueObjects\Slug;

/**
 * Adds a tag at the end of the list. Without a typed slug, one is made from the name.
 */
final class CreateTag
{
    public function __construct(
        private readonly TagRepository $tags,
        private readonly SlugGenerator $slugs,
    ) {}

    public function handle(CreateTagInput $input): int
    {
        return $this->tags->save(Tag::create(
            name: $input->name,
            slug: $this->slugFor($input),
            type: $input->type,
            sort: $this->tags->nextSortOrder(),
            icon: $input->icon,
        ));
    }

    private function slugFor(CreateTagInput $input): Slug
    {
        $typed = trim($input->slug ?? '');

        if ($typed === '') {
            return $this->slugs->generate($input->name, fn (Slug $slug): bool => $this->tags->slugExists($slug));
        }

        $slug = Slug::fromString($typed);

        if ($this->tags->slugExists($slug)) {
            throw ValidationFailed::forField('slug', 'This slug is already used (maybe by a tag in the Trash). Choose another one.');
        }

        return $slug;
    }
}
