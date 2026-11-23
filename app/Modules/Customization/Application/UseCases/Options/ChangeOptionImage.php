<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\UseCases\Options;

use App\Modules\Customization\Application\DTOs\ChangeOptionImageInput;
use App\Modules\Customization\Application\Services\OptionImages;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use Throwable;

/**
 * Uploads, replaces or removes the image of an option. The new file is stored first, then the
 * row is saved, then the old file is deleted. A failed save deletes the new file again.
 */
final class ChangeOptionImage
{
    public function __construct(
        private readonly OptionGroupRepository $groups,
        private readonly OptionImages $images,
    ) {}

    public function handle(ChangeOptionImageInput $input): void
    {
        $group = $this->groups->find($input->optionGroupId)
            ?? throw EntityNotFound::withId('Option group', $input->optionGroupId);
        $option = $group->option($input->optionId)
            ?? throw EntityNotFound::withId('Option', $input->optionId);

        $oldPath = $option->imagePath();
        $newPath = $input->image === null ? null : $this->images->store($input->image);

        try {
            $option->changeImage($newPath);
            $this->groups->save($group);
        } catch (Throwable $failure) {
            if ($newPath !== null) {
                $this->images->delete($newPath);
            }

            throw $failure;
        }

        if ($oldPath !== null) {
            $this->images->delete($oldPath);
        }
    }
}
