<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\UseCases\OptionGroups;

use App\Modules\Customization\Application\DTOs\CreateOptionGroupInput;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;

/**
 * Adds an active group without options at the end of the list. Returns its id.
 */
final class CreateOptionGroup
{
    public function __construct(private readonly OptionGroupRepository $groups) {}

    public function handle(CreateOptionGroupInput $input): int
    {
        return $this->groups->save(OptionGroup::create(
            $input->name,
            $input->displayTitle,
            $this->groups->nextSortOrder(),
            $input->description,
        ));
    }
}
