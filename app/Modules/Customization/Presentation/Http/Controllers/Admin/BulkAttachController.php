<?php

declare(strict_types=1);

namespace App\Modules\Customization\Presentation\Http\Controllers\Admin;

use App\Modules\Customization\Application\DTOs\BulkAttachOptionGroupInput;
use App\Modules\Customization\Application\Ports\CustomizableProducts;
use App\Modules\Customization\Application\Queries\AdminOptionQuery;
use App\Modules\Customization\Application\UseCases\Attachments\BulkAttachOptionGroup;
use App\Modules\Customization\Presentation\Http\Controllers\Admin\Concerns\RunsCustomizationActions;
use App\Modules\Customization\Presentation\Http\Requests\BulkAttachRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Attaches one option group to every product of a category.
 */
final class BulkAttachController
{
    use RunsCustomizationActions;

    public function create(int $group, AdminOptionQuery $query, CustomizableProducts $products): View
    {
        $detail = $query->group($group);

        if ($detail === null) {
            abort(404);
        }

        return view('customization::admin.option-groups.bulk-attach', [
            'group' => $detail,
            'categories' => $products->categoryNames(),
        ]);
    }

    public function store(int $group, BulkAttachRequest $request, BulkAttachOptionGroup $bulkAttach): RedirectResponse
    {
        $result = $this->runFormAction(fn () => $bulkAttach->handle(new BulkAttachOptionGroupInput(
            $request->integer('category_id'),
            $group,
            $request->rulesInput(),
        )));

        return redirect()->route('admin.option-groups.edit', $group)->with(
            'success',
            "Attached to {$result->attached} products. {$result->alreadyAttached} had the group already and kept their rules.",
        );
    }
}
