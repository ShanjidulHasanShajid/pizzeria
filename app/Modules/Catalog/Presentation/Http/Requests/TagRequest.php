<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Requests;

use App\Modules\Catalog\Domain\Entities\Tag;
use App\Modules\Catalog\Domain\Enums\TagType;
use App\Modules\Catalog\Presentation\Support\TagIcons;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Create and edit form of a tag. The icon must be one of the offered icons.
 */
final class TagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-catalog');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:'.Tag::NAME_MAX_LENGTH],
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'type' => ['required', Rule::enum(TagType::class)],
            'icon' => ['nullable', Rule::in(array_keys(TagIcons::OPTIONS))],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex' => 'Use lower-case letters, numbers and single hyphens only, for example "contains-nuts".',
        ];
    }
}
