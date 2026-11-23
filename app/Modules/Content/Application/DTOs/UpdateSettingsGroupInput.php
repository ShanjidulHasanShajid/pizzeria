<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\DTOs;

use App\Modules\Content\Domain\Enums\SettingGroup;

final readonly class UpdateSettingsGroupInput
{
    /**
     * @param  array<string, string>  $values  setting key => value as typed; keys of other groups are ignored
     * @param  array<string, ImageUpload>  $uploads  setting key => new image
     * @param  list<string>  $removedImages  setting keys whose image is removed
     */
    public function __construct(
        public SettingGroup $group,
        public array $values,
        public array $uploads = [],
        public array $removedImages = [],
    ) {}
}
