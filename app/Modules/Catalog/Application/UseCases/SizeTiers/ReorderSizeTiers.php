<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\SizeTiers;

use App\Modules\Catalog\Application\DTOs\ReorderInput;
use App\Modules\Catalog\Domain\Repositories\SizeTierRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * The size order is also the order of the size buttons on every pizza and pie page.
 */
final class ReorderSizeTiers
{
    public function __construct(
        private readonly SizeTierRepository $sizeTiers,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(ReorderInput $input): void
    {
        if (count(array_unique($input->ids)) !== count($input->ids)) {
            throw ValidationFailed::forField('ids', 'Each size may appear only once.');
        }

        $this->transactions->run(function () use ($input): void {
            $sort = SortOrder::first();

            foreach ($input->ids as $id) {
                $sizeTier = $this->sizeTiers->find($id)
                    ?? throw EntityNotFound::withId('Size tier', $id);

                $sizeTier->moveTo($sort);
                $this->sizeTiers->save($sizeTier);
                $sort = $sort->next();
            }
        });
    }
}
