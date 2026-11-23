<?php

declare(strict_types=1);

namespace App\Modules\Content\Presentation\Http\Requests;

use App\Modules\Content\Application\DTOs\ImageUpload;
use App\Modules\Content\Application\DTOs\SaveContentItemInput;
use App\Modules\Content\Domain\Enums\ContentType;
use App\Modules\Content\Domain\Enums\FieldKind;
use App\Modules\Content\Presentation\Support\ContentLabels;
use DateTimeImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

/**
 * The form of any content type. Here the shape is checked; required fields, lengths and
 * links are checked by the domain (ContentField).
 */
final class ContentItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-content');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = ['is_active' => ['sometimes', 'boolean']];

        foreach ($this->contentType()->fields() as $field) {
            $rules[$field->name] = match ($field->kind) {
                FieldKind::Image => ['nullable', self::imageRule()],
                FieldKind::Icon => ['nullable', Rule::in(array_keys(ContentLabels::ICONS))],
                FieldKind::Text, FieldKind::Link => ['nullable', 'string', 'max:1000'],
            };

            if ($field->isImage()) {
                $rules["remove_{$field->name}"] = ['sometimes', 'boolean'];
            }
        }

        if ($this->contentType()->isScheduled()) {
            $rules['starts_at'] = ['nullable', 'date'];
            $rules['ends_at'] = ['nullable', 'date'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.dimensions' => 'The image must be at least 400 pixels wide.',
            'mobile_image.dimensions' => 'The image must be at least 400 pixels wide.',
        ];
    }

    public static function imageRule(): File
    {
        return File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2 * 1024)->dimensions(Rule::dimensions()->minWidth(400));
    }

    public function toInput(?int $id): SaveContentItemInput
    {
        $values = [];
        $uploads = [];
        $removed = [];

        foreach ($this->contentType()->fields() as $field) {
            if (! $field->isImage()) {
                $values[$field->name] = $this->string($field->name)->toString();

                continue;
            }

            $file = $this->file($field->name);

            if ($file instanceof UploadedFile) {
                $uploads[$field->name] = new ImageUpload((string) $file->get());
            } elseif ($this->boolean("remove_{$field->name}")) {
                $removed[] = $field->name;
            }
        }

        return new SaveContentItemInput(
            $this->contentType(),
            $id,
            $values,
            $uploads,
            $removed,
            $this->moment('starts_at'),
            $this->moment('ends_at'),
            $this->boolean('is_active'),
        );
    }

    private function contentType(): ContentType
    {
        $type = $this->route('type');

        return $type instanceof ContentType ? $type : abort(404);
    }

    /** The admin types local time; the application time zone is Asia/Dhaka. */
    private function moment(string $field): ?DateTimeImmutable
    {
        return $this->date($field)?->toDateTimeImmutable();
    }
}
