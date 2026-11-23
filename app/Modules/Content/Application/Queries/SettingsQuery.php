<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\Queries;

use App\Modules\Content\Application\DTOs\SettingField;
use App\Modules\Content\Application\DTOs\SiteSettings;
use App\Modules\Content\Domain\Enums\SettingGroup;

interface SettingsQuery
{
    /** All settings with their real types. Other modules use this too (VAT, store open ...). */
    public function current(): SiteSettings;

    /**
     * The settings of one tab, for the admin form.
     *
     * @return list<SettingField>
     */
    public function fields(SettingGroup $group): array;
}
