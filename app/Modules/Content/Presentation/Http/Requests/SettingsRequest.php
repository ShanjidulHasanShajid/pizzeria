<?php

declare(strict_types=1);

namespace App\Modules\Content\Presentation\Http\Requests;

use App\Modules\Content\Application\DTOs\ImageUpload;
use App\Modules\Content\Application\DTOs\UpdateSettingsGroupInput;
use App\Modules\Content\Domain\Enums\SettingGroup;
use App\Modules\Content\Domain\Enums\SettingKey;
use App\Modules\Content\Domain\Enums\SettingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

/**
 * One tab of the settings screen. Here only the shape is checked (texts, image files);
 * whether a text is a valid email, percentage ... is checked by the domain.
 */
final class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-settings');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [];

        foreach ($this->group()->keys() as $key) {
            if ($key->type() === SettingType::Image) {
                $rules[$key->value] = ['nullable', $this->imageRule($key)];
                $rules["remove_{$key->value}"] = ['sometimes', 'boolean'];
            } else {
                $rules[$key->value] = ['nullable', 'string', 'max:1000'];
            }
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'logo.dimensions' => 'The logo must be at least 100 pixels wide.',
            'favicon.dimensions' => 'The browser icon must be square and at least 64 x 64 pixels.',
        ];
    }

    public function toInput(): UpdateSettingsGroupInput
    {
        $values = [];
        $uploads = [];
        $removed = [];

        foreach ($this->group()->keys() as $key) {
            if ($key->type() !== SettingType::Image) {
                // A field that was not sent keeps its value; a field that was emptied becomes "".
                if ($this->has($key->value)) {
                    $values[$key->value] = $this->string($key->value)->toString();
                }

                continue;
            }

            $file = $this->file($key->value);

            if ($file instanceof UploadedFile) {
                $uploads[$key->value] = new ImageUpload((string) $file->get());
            } elseif ($this->boolean("remove_{$key->value}")) {
                $removed[] = $key->value;
            }
        }

        return new UpdateSettingsGroupInput($this->group(), $values, $uploads, $removed);
    }

    private function group(): SettingGroup
    {
        $group = $this->route('group');

        return $group instanceof SettingGroup ? $group : abort(404);
    }

    private function imageRule(SettingKey $key): File
    {
        $dimensions = $key === SettingKey::Favicon
            ? Rule::dimensions()->minWidth(64)->minHeight(64)->ratio(1.0)
            : Rule::dimensions()->minWidth(100);

        return File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2 * 1024)->dimensions($dimensions);
    }
}
