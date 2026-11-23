<?php

declare(strict_types=1);

namespace App\Modules\Customization\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * The new order of a drag-and-drop list: {"ids": [3, 1, 2]}.
 */
final class ReorderRequest extends FormRequest
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
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        /** @var array<int, int|string> $ids */
        $ids = $this->validated('ids');

        return array_values(array_map(fn (int|string $id): int => (int) $id, $ids));
    }
}
