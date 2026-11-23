<?php

declare(strict_types=1);

namespace App\Modules\Content\Domain\Repositories;

/**
 * The saved settings as plain texts. What they mean is the registry's job (SettingKey).
 */
interface SettingsRepository
{
    /**
     * Only settings that were saved at least once.
     *
     * @return array<string, string> setting key => value
     */
    public function all(): array;

    /**
     * Saves these settings and leaves the others alone.
     *
     * @param  array<string, string>  $values  setting key => value
     */
    public function save(array $values): void;
}
