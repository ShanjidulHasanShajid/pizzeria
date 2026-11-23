<?php

declare(strict_types=1);

namespace App\Modules\Customization\Presentation\Http\Controllers\Admin;

use App\Modules\Customization\Application\DTOs\CreateOptionGroupInput;
use App\Modules\Customization\Application\DTOs\OptionGroupItemInput;
use App\Modules\Customization\Application\DTOs\ReorderOptionGroupsInput;
use App\Modules\Customization\Application\DTOs\UpdateOptionGroupInput;
use App\Modules\Customization\Application\Queries\AdminOptionQuery;
use App\Modules\Customization\Application\UseCases\OptionGroups\CreateOptionGroup;
use App\Modules\Customization\Application\UseCases\OptionGroups\DeleteOptionGroup;
use App\Modules\Customization\Application\UseCases\OptionGroups\ForceDeleteOptionGroup;
use App\Modules\Customization\Application\UseCases\OptionGroups\ReorderOptionGroups;
use App\Modules\Customization\Application\UseCases\OptionGroups\RestoreOptionGroup;
use App\Modules\Customization\Application\UseCases\OptionGroups\UpdateOptionGroup;
use App\Modules\Customization\Presentation\Http\Controllers\Admin\Concerns\RunsCustomizationActions;
use App\Modules\Customization\Presentation\Http\Requests\OptionGroupRequest;
use App\Modules\Customization\Presentation\Http\Requests\ReorderRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/**
 * Admin screens for option groups (Crust, Dips ...). Routes require "manage-catalog";
 * "delete forever" also requires "force-delete" (super admins).
 */
final class OptionGroupController
{
    use RunsCustomizationActions;

    public function index(AdminOptionQuery $query): View
    {
        return view('customization::admin.option-groups.index', ['groups' => $query->groupTable()]);
    }

    public function create(): View
    {
        return view('customization::admin.option-groups.create');
    }

    public function store(OptionGroupRequest $request, CreateOptionGroup $createGroup): RedirectResponse
    {
        $id = $this->runFormAction(fn () => $createGroup->handle(new CreateOptionGroupInput(
            name: $request->string('name')->toString(),
            displayTitle: $request->string('display_title')->toString(),
            description: $request->input('description'),
        )));

        return redirect()->route('admin.option-groups.edit', $id)->with('success', 'Option group created. Now add its options.');
    }

    public function edit(int $group, AdminOptionQuery $query): View
    {
        $detail = $query->group($group);

        if ($detail === null) {
            abort(404);
        }

        return view('customization::admin.option-groups.edit', ['group' => $detail]);
    }

    public function update(int $group, OptionGroupRequest $request, UpdateOptionGroup $updateGroup): RedirectResponse
    {
        $this->runFormAction(fn () => $updateGroup->handle(new UpdateOptionGroupInput(
            id: $group,
            name: $request->string('name')->toString(),
            displayTitle: $request->string('display_title')->toString(),
            description: $request->input('description'),
            isActive: $request->boolean('is_active'),
        )));

        return redirect()->route('admin.option-groups.index')->with('success', 'Option group saved.');
    }

    public function destroy(int $group, DeleteOptionGroup $deleteGroup): RedirectResponse
    {
        return $this->runListAction(
            fn () => $deleteGroup->handle(new OptionGroupItemInput($group)),
            'Option group moved to the trash.',
        );
    }

    public function restore(int $group, RestoreOptionGroup $restoreGroup): RedirectResponse
    {
        return $this->runListAction(
            fn () => $restoreGroup->handle(new OptionGroupItemInput($group)),
            'Option group restored.',
        );
    }

    public function forceDestroy(int $group, ForceDeleteOptionGroup $forceDelete): RedirectResponse
    {
        return $this->runListAction(
            fn () => $forceDelete->handle(new OptionGroupItemInput($group)),
            'Option group deleted forever.',
        );
    }

    public function reorder(ReorderRequest $request, ReorderOptionGroups $reorderGroups): Response
    {
        $this->runFormAction(fn () => $reorderGroups->handle(new ReorderOptionGroupsInput($request->ids())));

        return response()->noContent();
    }
}
