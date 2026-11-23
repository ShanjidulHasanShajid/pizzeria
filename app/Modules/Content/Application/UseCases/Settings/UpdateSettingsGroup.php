<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\UseCases\Settings;

use App\Modules\Content\Application\DTOs\UpdateSettingsGroupInput;
use App\Modules\Content\Application\Services\SettingImages;
use App\Modules\Content\Domain\Enums\SettingType;
use App\Modules\Content\Domain\Repositories\SettingsRepository;
use App\Modules\Content\Domain\Services\SettingValues;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use Throwable;

/**
 * Saves one tab of the settings screen. Every wrong field is reported at once, and then nothing
 * is saved. New image files are stored first; old ones are deleted only after the save.
 */
final class UpdateSettingsGroup
{
    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly SettingValues $rules,
        private readonly SettingImages $images,
    ) {}

    public function handle(UpdateSettingsGroupInput $input): void
    {
        $values = $this->cleanValues($input);
        $saved = $this->settings->all();
        $newPaths = [];
        $oldPaths = [];

        try {
            foreach ($input->group->keys() as $key) {
                if ($key->type() !== SettingType::Image) {
                    continue;
                }

                $upload = $input->uploads[$key->value] ?? null;

                if ($upload !== null) {
                    $values[$key->value] = $this->images->store($key, $upload);
                    $newPaths[] = $values[$key->value];
                } elseif (in_array($key->value, $input->removedImages, true)) {
                    $values[$key->value] = '';
                } else {
                    continue;
                }

                if (($saved[$key->value] ?? '') !== '') {
                    $oldPaths[] = $saved[$key->value];
                }
            }

            $this->settings->save($values);
        } catch (Throwable $failure) {
            $this->deleteFiles($newPaths);

            throw $failure;
        }

        $this->deleteFiles($oldPaths);
    }

    /**
     * @return array<string, string> setting key => checked value
     */
    private function cleanValues(UpdateSettingsGroupInput $input): array
    {
        $values = [];
        $errors = [];

        foreach ($input->group->keys() as $key) {
            if ($key->type() === SettingType::Image || ! isset($input->values[$key->value])) {
                continue;
            }

            try {
                $values[$key->value] = $this->rules->clean($key, $input->values[$key->value]);
            } catch (InvalidValue $invalid) {
                $errors[$key->value] = [$invalid->getMessage()];
            }
        }

        if ($errors !== []) {
            throw ValidationFailed::withErrors($errors);
        }

        return $values;
    }

    /**
     * @param  list<string>  $paths
     */
    private function deleteFiles(array $paths): void
    {
        foreach ($paths as $path) {
            $this->images->delete($path);
        }
    }
}
