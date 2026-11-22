<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Requests;

use App\Modules\Catalog\Application\DTOs\ProductFormInput;
use App\Modules\Catalog\Application\DTOs\VariantInput;
use App\Modules\Catalog\Domain\Entities\Product;
use App\Modules\Catalog\Domain\Entities\Variant;
use App\Modules\Catalog\Domain\Enums\LeadTimeClass;
use App\Modules\Catalog\Domain\ValueObjects\LeadTime;
use App\Modules\Shared\Domain\ValueObjects\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Create and edit form of a product (7.22). The variant rows arrive as an array:
 * variants[0][label], variants[0][price], ... and "default_variant" is the index of the default row.
 * Rules with "variants.*." apply to every row (nested validation).
 */
final class ProductRequest extends FormRequest
{
    public const MAX_VARIANTS = 20;

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
            'name' => ['required', 'string', 'max:'.Product::NAME_MAX_LENGTH],
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'short_description' => ['nullable', 'string', 'max:'.Product::SHORT_DESCRIPTION_MAX_LENGTH],
            'description' => ['nullable', 'string', 'max:'.Product::DESCRIPTION_MAX_LENGTH],
            'lead_time_class' => ['required', Rule::enum(LeadTimeClass::class)],
            'lead_time_minutes' => ['required', 'integer', 'min:0', 'max:'.LeadTime::MAX_MINUTES],
            'is_new' => ['sometimes', 'boolean'],
            'is_hot' => ['sometimes', 'boolean'],
            'is_published' => ['sometimes', 'boolean'],
            'is_available' => ['sometimes', 'boolean'],

            'variants' => ['required', 'array', 'min:1', 'max:'.self::MAX_VARIANTS],
            'variants.*.id' => ['nullable', 'integer', 'min:1'],
            'variants.*.label' => ['required', 'string', 'max:'.Variant::LABEL_MAX_LENGTH, 'distinct:ignore_case'],
            // Taka as the admin types it: 450 or 450.50 (never a float; Money reads the text).
            'variants.*.price' => ['required', 'string', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'variants.*.size_tier_id' => ['nullable', 'integer', 'min:1'],
            'variants.*.is_available' => ['sometimes', 'boolean'],
            'default_variant' => ['required', 'integer', 'min:0'],

            'tags' => ['sometimes', 'array'],
            'tags.*' => ['integer', 'min:1', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex' => 'Use lower-case letters, numbers and single hyphens only, for example "chicken-tikka".',
            'variants.required' => 'Add at least one variant.',
            'variants.*.label.required' => 'Give every variant a label, for example "Large" or "6 pcs".',
            'variants.*.label.distinct' => 'Two variants have the same label.',
            'variants.*.price.required' => 'Give every variant a price.',
            'variants.*.price.regex' => 'Enter the price in taka, for example 450 or 450.50.',
            'default_variant.required' => 'Choose the default variant.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'category',
            'lead_time_minutes' => 'minutes',
        ];
    }

    /**
     * The validated form as the use case's input. Prices become Money here (from text, never float).
     */
    public function toInput(): ProductFormInput
    {
        $default = $this->integer('default_variant');
        $variants = [];

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $this->validated('variants');

        foreach ($rows as $index => $row) {
            $variants[] = new VariantInput(
                id: isset($row['id']) ? (int) $row['id'] : null,
                label: (string) $row['label'],
                price: Money::fromTaka((string) $row['price']),
                sizeTierId: isset($row['size_tier_id']) ? (int) $row['size_tier_id'] : null,
                isDefault: (int) $index === $default,
                isAvailable: filter_var($row['is_available'] ?? true, FILTER_VALIDATE_BOOLEAN),
            );
        }

        /** @var array<int, int|string> $tags */
        $tags = $this->validated('tags', []);

        return new ProductFormInput(
            categoryId: $this->integer('category_id'),
            name: $this->string('name')->toString(),
            slug: $this->input('slug'),
            shortDescription: $this->input('short_description'),
            description: $this->input('description'),
            leadTimeClass: LeadTimeClass::from($this->string('lead_time_class')->toString()),
            leadTimeMinutes: $this->integer('lead_time_minutes'),
            isNew: $this->boolean('is_new'),
            isHot: $this->boolean('is_hot'),
            isPublished: $this->boolean('is_published'),
            isAvailable: $this->boolean('is_available'),
            variants: $variants,
            tagIds: array_values(array_map(fn (int|string $tag): int => (int) $tag, $tags)),
        );
    }
}
