<?php

declare(strict_types=1);

namespace App\Modules\Customization\Presentation\Http\Requests;

use App\Modules\Customization\Application\DTOs\SelectionRulesInput;
use App\Modules\Customization\Domain\ValueObjects\SelectionRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * A category and the rules for every product in it. Whether the numbers fit together
 * (minimum above maximum ...) is checked by the domain.
 */
final class BulkAttachRequest extends FormRequest
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
            'category_id' => ['required', 'integer', 'min:1'],
            'is_required' => ['sometimes', 'boolean'],
            'minimum' => ['required', 'integer', 'between:0,'.SelectionRules::MAX_PICKS],
            'maximum' => ['required', 'integer', 'between:1,'.SelectionRules::MAX_PICKS],
            'free_count' => ['required', 'integer', 'between:0,'.SelectionRules::MAX_PICKS],
        ];
    }

    public function rulesInput(): SelectionRulesInput
    {
        return new SelectionRulesInput(
            $this->boolean('is_required'),
            $this->integer('minimum'),
            $this->integer('maximum'),
            $this->integer('free_count'),
        );
    }
}
