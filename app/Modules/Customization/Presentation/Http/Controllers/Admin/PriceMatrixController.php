<?php

declare(strict_types=1);

namespace App\Modules\Customization\Presentation\Http\Controllers\Admin;

use App\Modules\Customization\Application\DTOs\SavePriceMatrixInput;
use App\Modules\Customization\Application\Ports\SizeTiers;
use App\Modules\Customization\Application\Queries\AdminOptionQuery;
use App\Modules\Customization\Application\UseCases\Options\SavePriceMatrix;
use App\Modules\Customization\Presentation\Http\Controllers\Admin\Concerns\RunsCustomizationActions;
use App\Modules\Customization\Presentation\Http\Requests\PriceMatrixRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * The price matrix of one option group: options as rows, sizes as columns.
 */
final class PriceMatrixController
{
    use RunsCustomizationActions;

    public function edit(int $group, AdminOptionQuery $query, SizeTiers $sizeTiers): View
    {
        $detail = $query->group($group);

        if ($detail === null) {
            abort(404);
        }

        return view('customization::admin.option-groups.prices', [
            'group' => $detail,
            'sizes' => $sizeTiers->names(),
        ]);
    }

    public function update(int $group, PriceMatrixRequest $request, SavePriceMatrix $saveMatrix): RedirectResponse
    {
        $this->runFormAction(fn () => $saveMatrix->handle(new SavePriceMatrixInput($group, $request->cells())));

        return redirect()->route('admin.option-groups.prices.edit', $group)->with('success', 'Prices saved.');
    }
}
