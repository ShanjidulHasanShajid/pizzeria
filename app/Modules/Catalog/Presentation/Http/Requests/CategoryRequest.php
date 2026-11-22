<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Requests;

use App\Modules\Catalog\Domain\Entities\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

/**
 * Create and edit form of a category. Checks the shape of the input; whether the parent,
 * the station and the slug are valid choices is checked by the use cases.
 */
final class CategoryRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:'.Category::NAME_MAX_LENGTH],
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'parent_id' => ['nullable', 'integer', 'min:1'],
            'kitchen_station_id' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:'.Category::DESCRIPTION_MAX_LENGTH],
            // JPG, PNG or WebP, at most 2 MB, at least 400 x 400 pixels (the thumbnail size).
            'image' => [
                'nullable',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max(2 * 1024)
                    ->dimensions(Rule::dimensions()->minWidth(400)->minHeight(400)),
            ],
            'remove_image' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'show_in_mega_menu' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex' => 'Use lower-case letters, numbers and single hyphens only, for example "soft-drinks".',
            'image.dimensions' => 'The image must be at least 400 x 400 pixels.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'parent_id' => 'parent',
            'kitchen_station_id' => 'kitchen station',
        ];
    }
}
