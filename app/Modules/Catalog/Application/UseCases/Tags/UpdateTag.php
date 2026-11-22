<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Tags;

use App\Modules\Catalog\Application\DTOs\UpdateTagInput;
use App\Modules\Catalog\Domain\Repositories\TagRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\ValueObjects\Slug;

/**
 * Changes name, slug (an empty slug keeps the current one), type, icon and the active switch.
 */
final class UpdateTag
{
    public function __construct(private readonly TagRepository $tags) {}

    public function handle(UpdateTagInput $input): void
    {
        $tag = $this->tags->find($input->id)
            ?? throw EntityNotFound::withId('Tag', $input->id);

        $typed = trim($input->slug ?? '');

        if ($typed !== '') {
            $slug = Slug::fromString($typed);

            if ($this->tags->slugExists($slug, ignoreId: $input->id)) {
                throw ValidationFailed::forField('slug', 'This slug is already used (maybe by a tag in the Trash). Choose another one.');
            }

            $tag->changeSlug($slug);
        }

        $tag->rename($input->name);
        $tag->changeType($input->type);
        $tag->changeIcon($input->icon);

        if ($input->isActive) {
            $tag->activate();
        } else {
            $tag->deactivate();
        }

        $this->tags->save($tag);
    }
}
