<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Tags;

use App\Modules\Catalog\Application\DTOs\ReorderInput;
use App\Modules\Catalog\Domain\Repositories\TagRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * The tag order is the order of the badges on product cards.
 */
final class ReorderTags
{
    public function __construct(
        private readonly TagRepository $tags,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(ReorderInput $input): void
    {
        if (count(array_unique($input->ids)) !== count($input->ids)) {
            throw ValidationFailed::forField('ids', 'Each tag may appear only once.');
        }

        $this->transactions->run(function () use ($input): void {
            $sort = SortOrder::first();

            foreach ($input->ids as $id) {
                $tag = $this->tags->find($id)
                    ?? throw EntityNotFound::withId('Tag', $id);

                $tag->moveTo($sort);
                $this->tags->save($tag);
                $sort = $sort->next();
            }
        });
    }
}
