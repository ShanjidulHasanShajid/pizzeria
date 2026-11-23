<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\UseCases\Options;

use App\Modules\Customization\Application\DTOs\OptionItemInput;
use App\Modules\Customization\Domain\Exceptions\InvalidOptionGroup;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * Moves an option to the Trash. Its image file stays, so the option can be restored.
 */
final class RemoveOption
{
    public function __construct(
        private readonly OptionGroupRepository $groups,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(OptionItemInput $input): void
    {
        $this->transactions->run(function () use ($input): void {
            $group = $this->groups->find($input->optionGroupId)
                ?? throw EntityNotFound::withId('Option group', $input->optionGroupId);

            try {
                $group->removeOption($input->optionId);
            } catch (InvalidOptionGroup) {
                throw EntityNotFound::withId('Option', $input->optionId);
            }

            $this->groups->save($group);
        });
    }
}
