<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\UseCases\Options;

use App\Modules\Customization\Application\DTOs\OptionItemInput;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * "Sold out" and back, for every product that offers the option. Returns true when it is available now.
 */
final class ToggleOptionAvailable
{
    public function __construct(
        private readonly OptionGroupRepository $groups,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(OptionItemInput $input): bool
    {
        return $this->transactions->run(function () use ($input): bool {
            $group = $this->groups->find($input->optionGroupId)
                ?? throw EntityNotFound::withId('Option group', $input->optionGroupId);
            $option = $group->option($input->optionId)
                ?? throw EntityNotFound::withId('Option', $input->optionId);

            if ($option->isAvailable()) {
                $option->markSoldOut();
            } else {
                $option->markAvailable();
            }

            $this->groups->save($group);

            return $option->isAvailable();
        });
    }
}
