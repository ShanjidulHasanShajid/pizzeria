<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\UseCases\Options;

use App\Modules\Customization\Application\DTOs\ReorderOptionsInput;
use App\Modules\Customization\Domain\Exceptions\InvalidOptionGroup;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;

/**
 * The order in which customers see the options of a group.
 */
final class ReorderOptions
{
    public function __construct(
        private readonly OptionGroupRepository $groups,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(ReorderOptionsInput $input): void
    {
        $this->transactions->run(function () use ($input): void {
            $group = $this->groups->find($input->optionGroupId)
                ?? throw EntityNotFound::withId('Option group', $input->optionGroupId);

            try {
                $group->reorderOptions($input->optionIds);
            } catch (InvalidOptionGroup $invalid) {
                throw ValidationFailed::forField('ids', $invalid->getMessage());
            }

            $this->groups->save($group);
        });
    }
}
