<?php

declare(strict_types=1);

namespace App\Modules\Content\Presentation\Http\Requests;

use App\Modules\Content\Application\DTOs\SaveLocationInput;
use App\Modules\Content\Domain\Enums\Weekday;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * The location form. Here the shape is checked; lengths, phone, map and hours are checked by the domain.
 */
final class LocationRequest extends FormRequest
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
        $rules = [
            'name' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:1000'],
            'area' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:1000'],
            'map_embed_url' => ['nullable', 'string', 'max:3000'],
            'latitude' => ['nullable', 'string', 'max:50'],
            'longitude' => ['nullable', 'string', 'max:50'],
            'is_pickup_available' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'hours' => ['sometimes', 'array'],
        ];

        foreach (Weekday::cases() as $day) {
            $rules["hours.{$day->value}"] = ['sometimes', 'array'];
            $rules["hours.{$day->value}.is_open"] = ['sometimes', 'boolean'];
            $rules["hours.{$day->value}.opens"] = ['nullable', 'string', 'max:8'];
            $rules["hours.{$day->value}.closes"] = ['nullable', 'string', 'max:8'];
        }

        return $rules;
    }

    public function toInput(?int $id): SaveLocationInput
    {
        $hours = [];

        foreach (Weekday::cases() as $day) {
            $hours[$day->value] = $this->boolean("hours.{$day->value}.is_open")
                ? [$this->string("hours.{$day->value}.opens")->toString(), $this->string("hours.{$day->value}.closes")->toString()]
                : null;
        }

        return new SaveLocationInput(
            $id,
            $this->string('name')->toString(),
            $this->string('address')->toString(),
            $this->string('area')->toString(),
            $this->string('phone')->toString(),
            $this->string('map_embed_url')->toString(),
            $this->string('latitude')->toString(),
            $this->string('longitude')->toString(),
            $hours,
            $this->boolean('is_pickup_available'),
            $this->boolean('is_active'),
        );
    }
}
