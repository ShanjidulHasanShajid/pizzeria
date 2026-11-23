<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\UseCases\OptionGroups;

use App\Modules\Customization\Application\DTOs\UpdateOptionGroupInput;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * Changes the texts of a group and switches it on or off. Its options stay as they are.
 */
final class UpdateOptionGroup
{
    public function __construct(
        private readonly OptionGroupRepository $groups,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(UpdateOptionGroupInput $input): void
    {
        $this->transactions->run(function () use ($input): void {
            $group = $this->groups->find($input->id)
                ?? throw EntityNotFound::withId('Option group', $input->id);

            $group->rename($input->name);
            $group->retitle($input->displayTitle);
            $group->describe($input->description);

            if ($input->isActive) {
                $group->activate();
            } else {
                $group->deactivate();
            }

            $this->groups->save($group);
        });
    }
}
