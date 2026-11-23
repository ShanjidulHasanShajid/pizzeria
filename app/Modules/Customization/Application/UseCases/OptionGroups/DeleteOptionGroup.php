<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\UseCases\OptionGroups;

use App\Modules\Customization\Application\DTOs\OptionGroupItemInput;
use App\Modules\Customization\Domain\Exceptions\OptionGroupInUse;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Customization\Domain\Repositories\ProductOptionGroupRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * Moves a group to the Trash. Refused while products offer it (business rules section 3).
 */
final class DeleteOptionGroup
{
    public function __construct(
        private readonly OptionGroupRepository $groups,
        private readonly ProductOptionGroupRepository $attachments,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(OptionGroupItemInput $input): void
    {
        $this->transactions->run(function () use ($input): void {
            $group = $this->groups->find($input->id)
                ?? throw EntityNotFound::withId('Option group', $input->id);

            if ($this->attachments->isGroupAttached($input->id)) {
                throw OptionGroupInUse::attachedToProducts($group->name());
            }

            $this->groups->delete($input->id);
        });
    }
}
