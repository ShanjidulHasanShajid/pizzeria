<?php

declare(strict_types=1);

namespace App\Modules\Content\Presentation\Http\Requests;

use App\Modules\Content\Application\DTOs\SaveHomeLayoutInput;
use App\Modules\Content\Domain\ValueObjects\HomeLayout;
use App\Modules\Content\Domain\ValueObjects\HomeRow;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * tabs[] = category ids; rows[0][category_id], rows[0][product_ids][]. Empty choices are left out.
 */
final class HomeLayoutRequest extends FormRequest
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
            'tabs' => ['nullable', 'array', 'max:'.HomeLayout::MAX_TABS],
            'tabs.*' => ['nullable', 'integer', 'min:1'],
            'rows' => ['nullable', 'array', 'max:'.HomeLayout::MAX_ROWS],
            'rows.*.category_id' => ['nullable', 'integer', 'min:1'],
            'rows.*.product_ids' => ['nullable', 'array', 'max:'.HomeRow::MAX_PRODUCTS],
            'rows.*.product_ids.*' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function toInput(): SaveHomeLayoutInput
    {
        $rows = [];

        /** @var array<int, array{category_id?: int|string|null, product_ids?: array<int, int|string|null>|null}> $given */
        $given = $this->validated('rows') ?? [];

        foreach ($given as $position => $row) {
            $categoryId = (int) ($row['category_id'] ?? 0);

            if ($categoryId > 0) {
                $rows[$position] = ['categoryId' => $categoryId, 'productIds' => $this->chosen($row['product_ids'] ?? [])];
            }
        }

        /** @var array<int, int|string|null> $tabs */
        $tabs = $this->validated('tabs') ?? [];

        return new SaveHomeLayoutInput($this->chosen($tabs), $rows);
    }

    /**
     * @param  array<int, int|string|null>  $ids
     * @return list<int>
     */
    private function chosen(array $ids): array
    {
        return array_values(array_filter(array_map(fn (int|string|null $id): int => (int) $id, $ids)));
    }
}
