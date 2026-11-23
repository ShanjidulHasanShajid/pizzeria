<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\UseCases\Options;

use App\Modules\Customization\Application\DTOs\TrashedOptionInput;
use App\Modules\Customization\Domain\Exceptions\OptionRestoreBlocked;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * Brings an option back into its group, with its prices and image. Refused while the group is in
 * the Trash, or when the group has another option with the same name by now.
 */
final class RestoreOption
{
    public function __construct(
        private readonly OptionGroupRepository $groups,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(TrashedOptionInput $input): void
    {
        $this->transactions->run(function () use ($input): void {
            $option = $this->groups->findTrashedOption($input->optionId);
            $groupId = $this->groups->groupIdOfTrashedOption($input->optionId);

            if ($option === null || $groupId === null) {
                throw EntityNotFound::withId('Option in the Trash', $input->optionId);
            }

            $group = $this->groups->find($groupId)
                ?? throw OptionRestoreBlocked::groupInTrash($option->name());

            if ($group->hasOptionNamed($option->name())) {
                throw OptionRestoreBlocked::nameTaken($option->name());
            }

            $this->groups->restoreOption($input->optionId);
        });
    }
}
