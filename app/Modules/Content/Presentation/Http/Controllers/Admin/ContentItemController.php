<?php

declare(strict_types=1);

namespace App\Modules\Content\Presentation\Http\Controllers\Admin;

use App\Modules\Content\Application\DTOs\ContentItemInput;
use App\Modules\Content\Application\DTOs\ReorderContentItemsInput;
use App\Modules\Content\Application\Queries\ContentQuery;
use App\Modules\Content\Application\UseCases\Items\AddGalleryImages;
use App\Modules\Content\Application\UseCases\Items\CreateContentItem;
use App\Modules\Content\Application\UseCases\Items\DeleteContentItem;
use App\Modules\Content\Application\UseCases\Items\ForceDeleteContentItem;
use App\Modules\Content\Application\UseCases\Items\ReorderContentItems;
use App\Modules\Content\Application\UseCases\Items\RestoreContentItem;
use App\Modules\Content\Application\UseCases\Items\ToggleContentItem;
use App\Modules\Content\Application\UseCases\Items\UpdateContentItem;
use App\Modules\Content\Domain\Enums\ContentType;
use App\Modules\Content\Presentation\Http\Controllers\Admin\Concerns\RunsContentActions;
use App\Modules\Content\Presentation\Http\Requests\ContentItemRequest;
use App\Modules\Content\Presentation\Http\Requests\GalleryUploadRequest;
use App\Modules\Content\Presentation\Http\Requests\ReorderRequest;
use App\Modules\Shared\Application\Ports\FileStorage;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/**
 * The admin screens of all home page lists. The routes say which ContentType is meant.
 * Routes require "manage-content". {item} comes before $type, because $type is a route default.
 */
final class ContentItemController
{
    use RunsContentActions;

    public function index(ContentType $type, ContentQuery $query, FileStorage $files): View
    {
        return view('content::admin.items.index', [
            'type' => $type,
            'items' => $query->table($type),
            'imageUrl' => $this->imageUrl($files),
        ]);
    }

    public function create(ContentType $type, FileStorage $files): View
    {
        return view('content::admin.items.create', ['type' => $type, 'imageUrl' => $this->imageUrl($files)]);
    }

    public function store(ContentType $type, ContentItemRequest $request, CreateContentItem $createItem): RedirectResponse
    {
        $this->runAction(fn () => $createItem->handle($request->toInput(null)));

        return redirect()->route("admin.{$type->value}.index")->with('success', $type->singular().' created.');
    }

    public function edit(int $item, ContentType $type, ContentQuery $query, FileStorage $files): View
    {
        $view = $query->item($type, $item);

        if ($view === null) {
            abort(404);
        }

        return view('content::admin.items.edit', ['type' => $type, 'item' => $view, 'imageUrl' => $this->imageUrl($files)]);
    }

    public function update(int $item, ContentType $type, ContentItemRequest $request, UpdateContentItem $updateItem): RedirectResponse
    {
        $this->runAction(fn () => $updateItem->handle($request->toInput($item)));

        return redirect()->route("admin.{$type->value}.index")->with('success', $type->singular().' saved.');
    }

    public function toggle(int $item, ContentType $type, ToggleContentItem $toggleItem): RedirectResponse
    {
        $this->runAction(fn () => $toggleItem->handle(new ContentItemInput($type, $item)));

        return back()->with('success', $type->singular().' switched.');
    }

    public function destroy(int $item, ContentType $type, DeleteContentItem $deleteItem): RedirectResponse
    {
        $this->runAction(fn () => $deleteItem->handle(new ContentItemInput($type, $item)));

        return back()->with('success', $type->singular().' moved to the trash.');
    }

    public function restore(int $item, ContentType $type, RestoreContentItem $restoreItem): RedirectResponse
    {
        $this->runAction(fn () => $restoreItem->handle(new ContentItemInput($type, $item)));

        return back()->with('success', $type->singular().' restored.');
    }

    public function forceDestroy(int $item, ContentType $type, ForceDeleteContentItem $forceDelete): RedirectResponse
    {
        $this->runAction(fn () => $forceDelete->handle(new ContentItemInput($type, $item)));

        return back()->with('success', $type->singular().' deleted forever.');
    }

    public function reorder(ContentType $type, ReorderRequest $request, ReorderContentItems $reorderItems): Response
    {
        $this->runAction(fn () => $reorderItems->handle(new ReorderContentItemsInput($type, $request->ids())));

        return response()->noContent();
    }

    /** Only the gallery has this route. */
    public function upload(GalleryUploadRequest $request, AddGalleryImages $addImages): RedirectResponse
    {
        $this->runAction(fn () => $addImages->handle($request->toInput()));

        return redirect()->route('admin.gallery.index')->with('success', 'Photos added.');
    }

    /**
     * @return Closure(string): ?string
     */
    private function imageUrl(FileStorage $files): Closure
    {
        return fn (string $path): ?string => $path === '' ? null : $files->url($path);
    }
}
