<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Persistence\Repositories;

use App\Modules\Content\Domain\Repositories\SettingsRepository;
use App\Modules\Content\Infrastructure\Persistence\Models\Setting as SettingModel;

final class EloquentSettingsRepository implements SettingsRepository
{
    public function all(): array
    {
        $values = [];

        foreach (SettingModel::query()->get() as $setting) {
            $values[$setting->key] = $setting->value;
        }

        return $values;
    }

    /**
     * One statement for all values: new keys are inserted, known keys updated.
     */
    public function save(array $values): void
    {
        if ($values === []) {
            return;
        }

        $rows = [];

        foreach ($values as $key => $value) {
            $rows[] = ['key' => $key, 'value' => $value];
        }

        SettingModel::query()->upsert($rows, ['key'], ['value']);
    }
}
