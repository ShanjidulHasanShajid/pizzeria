<?php

declare(strict_types=1);

namespace App\Modules\Content\Presentation\Http\Requests;

use App\Modules\Content\Application\DTOs\AddGalleryImagesInput;
use App\Modules\Content\Application\DTOs\ImageUpload;
use App\Modules\Content\Application\UseCases\Items\AddGalleryImages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

/**
 * Several photos at once: images[].
 */
final class GalleryUploadRequest extends FormRequest
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
            'images' => ['required', 'array', 'max:'.AddGalleryImages::MAX_IMAGES],
            'images.*' => ['required', ContentItemRequest::imageRule()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'images.required' => 'Choose one or more photos first.',
            'images.*.dimensions' => 'Each photo must be at least 400 pixels wide.',
        ];
    }

    public function toInput(): AddGalleryImagesInput
    {
        $images = [];

        foreach ((array) $this->file('images') as $file) {
            if ($file instanceof UploadedFile) {
                $images[] = new ImageUpload((string) $file->get());
            }
        }

        return new AddGalleryImagesInput($images);
    }
}
