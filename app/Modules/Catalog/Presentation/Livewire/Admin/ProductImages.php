<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Livewire\Admin;

use App\Modules\Catalog\Application\DTOs\ImageUpload;
use App\Modules\Catalog\Application\DTOs\ProductImageInput;
use App\Modules\Catalog\Application\DTOs\ReorderProductImagesInput;
use App\Modules\Catalog\Application\DTOs\UploadProductImagesInput;
use App\Modules\Catalog\Application\Queries\AdminCatalogQuery;
use App\Modules\Catalog\Application\UseCases\Products\DeleteProductImage;
use App\Modules\Catalog\Application\UseCases\Products\ReorderProductImages;
use App\Modules\Catalog\Application\UseCases\Products\SetMainProductImage;
use App\Modules\Catalog\Application\UseCases\Products\UploadProductImages;
use App\Modules\Shared\Application\Ports\FileStorage;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Presentation\Support\ValidationFailedTranslator;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * The image manager on the product edit page (7.19, 7.26): upload several images with a
 * preview, choose the main image, drag to reorder, delete. Every action calls one use case.
 * Used as <livewire:catalog.admin.product-images :product-id="..." />.
 */
final class ProductImages extends Component
{
    use WithFileUploads;

    /** Locked: the browser cannot change which product this component works on. */
    #[Locked]
    public int $productId;

    /** @var array<int, TemporaryUploadedFile> files chosen in the browser, not saved yet */
    public array $uploads = [];

    public string $status = '';

    private AdminCatalogQuery $query;

    private FileStorage $files;

    /**
     * Runs on EVERY request of this component. Livewire actions arrive at /livewire/update,
     * not at the admin routes, so the route middleware does not protect them: the gate is
     * checked here again. Dependencies are injected by Laravel's container.
     */
    public function boot(AdminCatalogQuery $query, FileStorage $files): void
    {
        abort_unless(Gate::allows('manage-catalog'), 403);

        $this->query = $query;
        $this->files = $files;
    }

    public function save(UploadProductImages $uploadImages): void
    {
        $this->validate([
            'uploads' => ['required', 'array', 'max:'.UploadProductImages::MAX_IMAGES],
            'uploads.*' => [
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max(2 * 1024)
                    ->dimensions(Rule::dimensions()->minWidth(400)->minHeight(400)),
            ],
        ], [
            'uploads.required' => 'Choose one or more images first.',
            'uploads.*.dimensions' => 'Each image must be at least 400 x 400 pixels.',
        ]);

        $images = array_values(array_map(
            fn (TemporaryUploadedFile $file): ImageUpload => new ImageUpload((string) $file->get()),
            $this->uploads,
        ));

        $this->run(fn () => $uploadImages->handle(new UploadProductImagesInput($this->productId, $images)));

        $this->reset('uploads');
        $this->status = count($images) === 1 ? 'Image added.' : count($images).' images added.';
    }

    public function setMain(SetMainProductImage $setMain, string $originalPath): void
    {
        $this->run(fn () => $setMain->handle(new ProductImageInput($this->productId, $originalPath)));
        $this->status = 'Main image changed.';
    }

    /**
     * @param  array<int, string>  $originalPaths  sent by the drag-and-drop list (the browser decides the keys)
     */
    public function reorder(ReorderProductImages $reorderImages, array $originalPaths): void
    {
        $this->run(fn () => $reorderImages->handle(new ReorderProductImagesInput($this->productId, array_values($originalPaths))));
        $this->status = 'New order saved.';
    }

    public function remove(DeleteProductImage $deleteImage, string $originalPath): void
    {
        $this->run(fn () => $deleteImage->handle(new ProductImageInput($this->productId, $originalPath)));
        $this->status = 'Image deleted.';
    }

    public function render(): View
    {
        return view('catalog::livewire.product-images', [
            'images' => $this->query->product($this->productId)->images ?? [],
            'imageUrl' => $this->imageUrl(),
        ]);
    }

    /**
     * A broken rule becomes a normal Livewire validation error (shown under the upload field).
     */
    private function run(Closure $action): void
    {
        try {
            $action();
        } catch (ValidationFailed $failure) {
            throw (new ValidationFailedTranslator)->toValidationException($failure);
        } catch (EntityNotFound) {
            abort(404);
        }
    }

    /**
     * @return Closure(string): string
     */
    private function imageUrl(): Closure
    {
        return fn (string $path): string => $this->files->url($path);
    }
}
