<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\SizeTiers;

use App\Modules\Catalog\Application\DTOs\CreateSizeTierInput;
use App\Modules\Catalog\Domain\Entities\SizeTier;
use App\Modules\Catalog\Domain\Repositories\SizeTierRepository;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\Services\SlugGenerator;
use App\Modules\Shared\Domain\ValueObjects\Slug;

/**
 * Adds a size at the end of the list. Without a typed slug, one is made from the name
 * (Medium -> medium, or medium-2 when medium is taken, also by an item in the Trash).
 */
final class CreateSizeTier
{
    public function __construct(
        private readonly SizeTierRepository $sizeTiers,
        private readonly SlugGenerator $slugs,
    ) {}

    public function handle(CreateSizeTierInput $input): int
    {
        return $this->sizeTiers->save(
            SizeTier::create($input->name, $this->slugFor($input), $this->sizeTiers->nextSortOrder()),
        );
    }

    private function slugFor(CreateSizeTierInput $input): Slug
    {
        $typed = trim($input->slug ?? '');

        if ($typed === '') {
            return $this->slugs->generate($input->name, fn (Slug $slug): bool => $this->sizeTiers->slugExists($slug));
        }

        $slug = Slug::fromString($typed);

        if ($this->sizeTiers->slugExists($slug)) {
            throw ValidationFailed::forField('slug', 'This slug is already used (maybe by a size in the Trash). Choose another one.');
        }

        return $slug;
    }
}
