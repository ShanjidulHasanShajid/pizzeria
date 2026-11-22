<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\SizeTiers;

use App\Modules\Catalog\Application\DTOs\UpdateSizeTierInput;
use App\Modules\Catalog\Domain\Repositories\SizeTierRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\ValueObjects\Slug;

/**
 * Renames a size, changes its slug (an empty slug keeps the current one) and switches it on or off.
 */
final class UpdateSizeTier
{
    public function __construct(private readonly SizeTierRepository $sizeTiers) {}

    public function handle(UpdateSizeTierInput $input): void
    {
        $sizeTier = $this->sizeTiers->find($input->id)
            ?? throw EntityNotFound::withId('Size tier', $input->id);

        $typed = trim($input->slug ?? '');

        if ($typed !== '') {
            $slug = Slug::fromString($typed);

            if ($this->sizeTiers->slugExists($slug, ignoreId: $input->id)) {
                throw ValidationFailed::forField('slug', 'This slug is already used (maybe by a size in the Trash). Choose another one.');
            }

            $sizeTier->changeSlug($slug);
        }

        $sizeTier->rename($input->name);

        if ($input->isActive) {
            $sizeTier->activate();
        } else {
            $sizeTier->deactivate();
        }

        $this->sizeTiers->save($sizeTier);
    }
}
