<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

final readonly class ChangeOptionImageInput
{
    /**
     * @param  ?ImageUpload  $image  null removes the current image
     */
    public function __construct(
        public int $optionGroupId,
        public int $optionId,
        public ?ImageUpload $image,
    ) {}
}
