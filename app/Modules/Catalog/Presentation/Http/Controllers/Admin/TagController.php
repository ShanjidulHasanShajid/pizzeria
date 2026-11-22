<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Controllers\Admin;

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Application\DTOs\CreateTagInput;
use App\Modules\Catalog\Application\DTOs\ReorderInput;
use App\Modules\Catalog\Application\DTOs\UpdateTagInput;
use App\Modules\Catalog\Application\Queries\AdminCatalogQuery;
use App\Modules\Catalog\Application\UseCases\Tags\CreateTag;
use App\Modules\Catalog\Application\UseCases\Tags\DeleteTag;
use App\Modules\Catalog\Application\UseCases\Tags\ReorderTags;
use App\Modules\Catalog\Application\UseCases\Tags\RestoreTag;
use App\Modules\Catalog\Application\UseCases\Tags\UpdateTag;
use App\Modules\Catalog\Domain\Enums\TagType;
use App\Modules\Catalog\Presentation\Http\Controllers\Admin\Concerns\RunsCatalogActions;
use App\Modules\Catalog\Presentation\Http\Requests\ReorderRequest;
use App\Modules\Catalog\Presentation\Http\Requests\TagRequest;
use App\Modules\Catalog\Presentation\Support\TagIcons;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/**
 * Admin screens for tags (Veg, Spicy, Contains Nuts ...). Routes require "manage-catalog".
 */
final class TagController
{
    use RunsCatalogActions;

    public function index(AdminCatalogQuery $query): View
    {
        return view('catalog::admin.tags.index', ['tags' => $query->tagTable()]);
    }

    public function create(): View
    {
        return view('catalog::admin.tags.create', $this->formOptions());
    }

    public function store(TagRequest $request, CreateTag $createTag): RedirectResponse
    {
        $this->runFormAction(fn () => $createTag->handle(new CreateTagInput(
            name: $request->string('name')->toString(),
            slug: $request->input('slug'),
            type: TagType::from($request->string('type')->toString()),
            icon: $request->input('icon'),
        )));

        return redirect()->route('admin.tags.index')->with('success', 'Tag created.');
    }

    public function edit(int $tag, AdminCatalogQuery $query): View
    {
        $row = $query->tag($tag);

        if ($row === null) {
            abort(404);
        }

        return view('catalog::admin.tags.edit', ['tag' => $row, ...$this->formOptions()]);
    }

    public function update(int $tag, TagRequest $request, UpdateTag $updateTag): RedirectResponse
    {
        $this->runFormAction(fn () => $updateTag->handle(new UpdateTagInput(
            id: $tag,
            name: $request->string('name')->toString(),
            slug: $request->input('slug'),
            type: TagType::from($request->string('type')->toString()),
            icon: $request->input('icon'),
            isActive: $request->boolean('is_active'),
        )));

        return redirect()->route('admin.tags.index')->with('success', 'Tag saved.');
    }

    public function destroy(int $tag, DeleteTag $deleteTag): RedirectResponse
    {
        return $this->runListAction(
            fn () => $deleteTag->handle(new CatalogItemInput($tag)),
            'Tag moved to the trash. Products keep it, the shop hides it.',
        );
    }

    public function restore(int $tag, RestoreTag $restoreTag): RedirectResponse
    {
        return $this->runListAction(
            fn () => $restoreTag->handle(new CatalogItemInput($tag)),
            'Tag restored.',
        );
    }

    public function reorder(ReorderRequest $request, ReorderTags $reorderTags): Response
    {
        $this->runFormAction(fn () => $reorderTags->handle(new ReorderInput($request->ids())));

        return response()->noContent();
    }

    /**
     * @return array{types: array<string, string>, icons: array<string, string>}
     */
    private function formOptions(): array
    {
        $types = [];

        foreach (TagType::cases() as $type) {
            $types[$type->value] = $type->label();
        }

        return ['types' => $types, 'icons' => TagIcons::OPTIONS];
    }
}
