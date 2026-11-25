<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Presentation\Http\Requests;

use App\Modules\Ordering\Application\DTOs\SaveDeliveryZoneInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * The delivery zone form. Here the shape is checked; names, amounts and the branch are checked by the domain.
 */
final class DeliveryZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-sales');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:1000'],
            'location_id' => ['nullable', 'integer'],
            'fee' => ['nullable', 'string', 'max:50'],
            'min_order' => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function toInput(?int $id): SaveDeliveryZoneInput
    {
        return new SaveDeliveryZoneInput(
            $id,
            $this->string('name')->toString(),
            $this->integer('location_id'),
            $this->string('fee')->toString(),
            $this->string('min_order')->toString(),
            $this->boolean('is_active'),
        );
    }
}
