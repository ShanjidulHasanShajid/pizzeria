<?php

declare(strict_types=1);

namespace App\Modules\Customization\Presentation\Http\Requests;

use App\Modules\Customization\Domain\Entities\OptionGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Create and edit form of an option group.
 */
final class OptionGroupRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:'.OptionGroup::NAME_MAX_LENGTH],
            'display_title' => ['required', 'string', 'max:'.OptionGroup::TITLE_MAX_LENGTH],
            'description' => ['nullable', 'string', 'max:'.OptionGroup::DESCRIPTION_MAX_LENGTH],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
