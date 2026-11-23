<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\UseCases\Items;

use App\Modules\Content\Application\DTOs\ReorderContentItemsInput;
use App\Modules\Content\Domain\Repositories\ContentItemRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

final class ReorderContentItems
{
    public function __construct(
        private readonly ContentItemRepository $items,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(ReorderContentItemsInput $input): void
    {
        if (count(array_unique($input->ids)) !== count($input->ids)) {
            throw ValidationFailed::forField('ids', 'Each item may appear only once.');
        }

        $this->transactions->run(function () use ($input): void {
            $sort = SortOrder::first();

            foreach ($input->ids as $id) {
                $item = $this->items->find($input->type, $id)
                    ?? throw EntityNotFound::withId($input->type->singular(), $id);

                $this->items->save($item->movedTo($sort));
                $sort = $sort->next();
            }
        });
    }
}
