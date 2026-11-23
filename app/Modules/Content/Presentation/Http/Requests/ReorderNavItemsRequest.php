<?php

declare(strict_types=1);

namespace App\Modules\Content\Presentation\Http\Requests;

use App\Modules\Content\Application\DTOs\ReorderNavItemsInput;
use App\Modules\Content\Domain\Enums\NavRoute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * The new order of the navbar: {"ids": ["menu", "home", ...]}.
 */
final class ReorderNavItemsRequest extends FormRequest
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
        return [
            'ids' => ['required', 'array', 'max:'.count(NavRoute::cases())],
            'ids.*' => ['required', 'string', 'distinct', Rule::enum(NavRoute::class)],
        ];
    }

    public function toInput(): ReorderNavItemsInput
    {
        /** @var array<int, string> $ids */
        $ids = $this->validated('ids');

        return new ReorderNavItemsInput(array_values(array_map(NavRoute::from(...), $ids)));
    }
}
