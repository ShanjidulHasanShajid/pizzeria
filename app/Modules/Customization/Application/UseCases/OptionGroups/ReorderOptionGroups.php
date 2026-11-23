<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\UseCases\OptionGroups;

use App\Modules\Customization\Application\DTOs\ReorderOptionGroupsInput;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * The order of the groups in the admin list. Each product has its own order (ReorderAttachedGroups).
 */
final class ReorderOptionGroups
{
    public function __construct(
        private readonly OptionGroupRepository $groups,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(ReorderOptionGroupsInput $input): void
    {
        if (count(array_unique($input->ids)) !== count($input->ids)) {
            throw ValidationFailed::forField('ids', 'Each option group may appear only once.');
        }

        $this->transactions->run(function () use ($input): void {
            $sort = SortOrder::first();

            foreach ($input->ids as $id) {
                $group = $this->groups->find($id)
                    ?? throw EntityNotFound::withId('Option group', $id);

                $group->moveTo($sort);
                $this->groups->save($group);
                $sort = $sort->next();
            }
        });
    }
}
