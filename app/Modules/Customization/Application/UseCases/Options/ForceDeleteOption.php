<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\UseCases\Options;

use App\Modules\Customization\Application\DTOs\TrashedOptionInput;
use App\Modules\Customization\Application\Services\OptionImages;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * "Delete forever" (super admins only, checked by the route), only from the Trash.
 * The prices go with the option; then its image file.
 */
final class ForceDeleteOption
{
    public function __construct(
        private readonly OptionGroupRepository $groups,
        private readonly OptionImages $images,
    ) {}

    public function handle(TrashedOptionInput $input): void
    {
        $option = $this->groups->findTrashedOption($input->optionId)
            ?? throw EntityNotFound::withId('Option in the Trash', $input->optionId);

        $this->groups->forceDeleteOption($input->optionId);

        $path = $option->imagePath();

        if ($path !== null) {
            $this->images->delete($path);
        }
    }
}
