<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\UseCases\OptionGroups;

use App\Modules\Customization\Application\DTOs\OptionGroupItemInput;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;

/**
 * Brings a group back from the Trash with its options. It depends on nothing else.
 */
final class RestoreOptionGroup
{
    public function __construct(private readonly OptionGroupRepository $groups) {}

    public function handle(OptionGroupItemInput $input): void
    {
        $this->groups->restore($input->id);
    }
}
