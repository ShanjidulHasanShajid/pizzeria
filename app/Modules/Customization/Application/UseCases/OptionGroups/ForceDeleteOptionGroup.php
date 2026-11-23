<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\UseCases\OptionGroups;

use App\Modules\Customization\Application\DTOs\OptionGroupItemInput;
use App\Modules\Customization\Application\Services\OptionImages;
use App\Modules\Customization\Domain\Exceptions\OptionGroupInUse;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Customization\Domain\Repositories\ProductOptionGroupRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * "Delete forever" (super admins only, checked by the route), only from the Trash and only
 * when no product has the group. Its options, prices and image files go with it.
 */
final class ForceDeleteOptionGroup
{
    public function __construct(
        private readonly OptionGroupRepository $groups,
        private readonly ProductOptionGroupRepository $attachments,
        private readonly OptionImages $images,
    ) {}

    public function handle(OptionGroupItemInput $input): void
    {
        $group = $this->groups->findTrashed($input->id)
            ?? throw EntityNotFound::withId('Option group in the Trash', $input->id);

        if ($this->attachments->isGroupAttached($input->id)) {
            throw OptionGroupInUse::attachedToProducts($group->name());
        }

        $paths = $this->groups->imagePaths($input->id);
        $this->groups->forceDelete($input->id);

        // Files last: if deleting the row had failed, the options would still need them.
        foreach ($paths as $path) {
            $this->images->delete($path);
        }
    }
}
