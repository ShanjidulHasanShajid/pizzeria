<?php

declare(strict_types=1);

namespace App\Modules\Customization\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * prices[option id][size tier id] = taka. An empty cell means "not offered at this size".
 */
final class PriceMatrixRequest extends FormRequest
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
            'prices' => ['required', 'array', 'max:200'],
            'prices.*' => ['required', 'array', 'max:50'],
            'prices.*.*' => ['nullable', 'string', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'prices.*.*.regex' => 'Enter taka, for example 150 or 150.50. Leave empty when not offered.',
        ];
    }

    /**
     * @return array<int, array<int, ?string>>
     */
    public function cells(): array
    {
        /** @var array<int|string, array<int|string, ?string>> $prices */
        $prices = $this->validated('prices');
        $cells = [];

        foreach ($prices as $optionId => $row) {
            foreach ($row as $sizeTierId => $taka) {
                $cells[(int) $optionId][(int) $sizeTierId] = $taka;
            }
        }

        return $cells;
    }
}
