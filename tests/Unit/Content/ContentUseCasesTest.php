<?php

declare(strict_types=1);

use App\Modules\Content\Application\DTOs\AddGalleryImagesInput;
use App\Modules\Content\Application\DTOs\ContentItemInput;
use App\Modules\Content\Application\DTOs\ImageUpload;
use App\Modules\Content\Application\DTOs\ReorderContentItemsInput;
use App\Modules\Content\Application\DTOs\SaveContentItemInput;
use App\Modules\Content\Application\Services\ContentImages;
use App\Modules\Content\Application\Services\ContentInputReader;
use App\Modules\Content\Application\UseCases\Items\AddGalleryImages;
use App\Modules\Content\Application\UseCases\Items\CreateContentItem;
use App\Modules\Content\Application\UseCases\Items\DeleteContentItem;
use App\Modules\Content\Application\UseCases\Items\ForceDeleteContentItem;
use App\Modules\Content\Application\UseCases\Items\ReorderContentItems;
use App\Modules\Content\Application\UseCases\Items\RestoreContentItem;
use App\Modules\Content\Application\UseCases\Items\ToggleContentItem;
use App\Modules\Content\Application\UseCases\Items\UpdateContentItem;
use App\Modules\Content\Domain\Enums\ContentType;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use Tests\Unit\Catalog\Fakes\InMemoryFileStorage;
use Tests\Unit\Catalog\Fakes\RecordingImageProcessor;
use Tests\Unit\Content\Fakes\InMemoryContentItemRepository;
use Tests\Unit\Identity\PassThroughTransactionManager;

/**
 * The banner form as the admin sends it.
 *
 * @param  array<string, mixed>  $changes
 */
function bannerForm(array $changes = []): SaveContentItemInput
{
    return new SaveContentItemInput(...[
        'type' => ContentType::Banner,
        'id' => null,
        'values' => ['title' => 'Winter offer', 'link' => '/menu'],
        'uploads' => [],
        'removedImages' => [],
        'startsAt' => null,
        'endsAt' => null,
        'isActive' => true,
        ...$changes,
    ]);
}

beforeEach(function (): void {
    $this->items = new InMemoryContentItemRepository;
    $this->files = new InMemoryFileStorage;
    $this->images = new ContentImages($this->files, new RecordingImageProcessor);
    $this->transactions = new PassThroughTransactionManager;
    $reader = new ContentInputReader;
    $this->create = fn (array $changes = []): int => (new CreateContentItem($this->items, $reader, $this->images))
        ->handle(bannerForm(['uploads' => ['image' => new ImageUpload('photo')], ...$changes]));
    $this->update = fn (int $id, array $changes = []) => (new UpdateContentItem($this->items, $reader, $this->images))
        ->handle(bannerForm(['id' => $id, ...$changes]));
    $this->find = fn (int $id) => $this->items->find(ContentType::Banner, $id);
});

it('creates items at the end of the list with their image', function (): void {
    ($this->create)();
    $id = ($this->create)(['values' => ['title' => 'Second', 'link' => '']]);

    $item = ($this->find)($id);

    expect($item->sort->value())->toBe(20)
        ->and($item->value('title'))->toBe('Second')
        ->and($this->files->contents($item->value('image')))->toBe('webp(resize-1200x800(photo))');
});

it('checks everything before it stores a file', function (): void {
    expect(fn () => ($this->create)(['values' => ['title' => '', 'link' => '']]))->toThrow(ValidationFailed::class);
    expect(fn () => ($this->create)(['uploads' => []]))->toThrow(ValidationFailed::class);
    expect(fn () => ($this->create)(['startsAt' => new DateTimeImmutable('2026-12-02'), 'endsAt' => new DateTimeImmutable('2026-12-01')]))->toThrow(ValidationFailed::class);

    expect($this->files->paths())->toBe([])
        ->and($this->items->all(ContentType::Banner))->toBe([]);
});

it('never takes an image path from the form', function (): void {
    $id = ($this->create)(['values' => ['title' => 'Offer', 'link' => '', 'image' => '../../secret.env']]);

    expect(($this->find)($id)->value('image'))->toStartWith('content/banners/');

    ($this->update)($id, ['values' => ['title' => 'Offer', 'link' => '', 'image' => '../../secret.env']]);

    expect(($this->find)($id)->value('image'))->toStartWith('content/banners/');
});

it('changes texts and dates and keeps the image', function (): void {
    $id = ($this->create)();
    $image = ($this->find)($id)->value('image');

    ($this->update)($id, ['values' => ['title' => 'New', 'link' => ''], 'startsAt' => new DateTimeImmutable('2026-12-01 10:00'), 'isActive' => false]);

    $item = ($this->find)($id);

    expect($item->value('title'))->toBe('New')
        ->and($item->value('image'))->toBe($image)
        ->and($item->schedule->start()->format('Y-m-d H:i'))->toBe('2026-12-01 10:00')
        ->and($item->isActive)->toBeFalse();
});

it('replaces an image together with its old file, but does not remove a required one', function (): void {
    $id = ($this->create)();

    ($this->update)($id, ['uploads' => ['image' => new ImageUpload('new')]]);

    expect($this->files->paths())->toBe([($this->find)($id)->value('image')]);
    expect(fn () => ($this->update)($id, ['removedImages' => ['image']]))->toThrow(ValidationFailed::class);
    expect($this->files->paths())->toHaveCount(1);
});

it('removes an optional image with its file', function (): void {
    $slide = ['type' => ContentType::HeroSlide, 'values' => ['title' => 'Hello']];
    $id = ($this->create)([...$slide, 'uploads' => ['image' => new ImageUpload('wide'), 'mobile_image' => new ImageUpload('tall')]]);

    ($this->update)($id, [...$slide, 'removedImages' => ['mobile_image']]);

    $item = $this->items->find(ContentType::HeroSlide, $id);

    expect($item->value('mobile_image'))->toBe('')
        ->and($this->files->paths())->toBe([$item->value('image')]);
});

it('deletes new files again when saving fails, and keeps the old ones', function (): void {
    $id = ($this->create)();
    $old = ($this->find)($id)->value('image');

    $this->items->failNextSave();
    expect(fn () => ($this->update)($id, ['uploads' => ['image' => new ImageUpload('new')]]))->toThrow(RuntimeException::class);
    $this->items->failNextSave();
    expect(fn () => ($this->create)())->toThrow(RuntimeException::class);

    expect($this->files->paths())->toBe([$old]);
});

it('switches, deletes and restores an item and keeps its files', function (): void {
    $id = ($this->create)();
    $input = new ContentItemInput(ContentType::Banner, $id);

    expect((new ToggleContentItem($this->items))->handle($input))->toBeFalse();

    (new DeleteContentItem($this->items))->handle($input);
    expect(($this->find)($id))->toBeNull()
        ->and($this->files->paths())->toHaveCount(1);

    (new RestoreContentItem($this->items))->handle($input);
    expect(($this->find)($id)->isActive)->toBeFalse();
});

it('saves a new order and refuses an item twice or an unknown one', function (): void {
    $first = ($this->create)();
    $second = ($this->create)();
    $reorder = fn (array $ids) => (new ReorderContentItems($this->items, $this->transactions))->handle(new ReorderContentItemsInput(ContentType::Banner, $ids));

    $reorder([$second, $first]);

    expect(($this->find)($second)->sort->value())->toBe(10);
    expect(fn () => $reorder([$first, $first]))->toThrow(ValidationFailed::class);
    expect(fn () => $reorder([$first, 99]))->toThrow(EntityNotFound::class);
});

it('reports an unknown item, also one of another type', function (): void {
    $id = ($this->create)();

    expect(fn () => ($this->update)(99))->toThrow(EntityNotFound::class);
    expect(fn () => (new DeleteContentItem($this->items))->handle(new ContentItemInput(ContentType::GalleryImage, $id)))->toThrow(EntityNotFound::class);
    expect(fn () => (new ToggleContentItem($this->items))->handle(new ContentItemInput(ContentType::Banner, 99)))->toThrow(EntityNotFound::class);
});

it('adds several gallery photos at once, all or none', function (): void {
    $add = fn (string ...$photos) => (new AddGalleryImages($this->items, $this->images, $this->transactions))
        ->handle(new AddGalleryImagesInput(array_map(fn (string $photo): ImageUpload => new ImageUpload($photo), array_values($photos))));

    $add('one', 'two');

    expect($this->items->all(ContentType::GalleryImage))->toHaveCount(2)
        ->and($this->items->all(ContentType::GalleryImage)[1]->sort->value())->toBe(20)
        ->and($this->files->paths())->toHaveCount(2);

    $this->items->failNextSave();
    expect(fn () => $add('three'))->toThrow(RuntimeException::class);
    expect($this->files->paths())->toHaveCount(2);
});

it('deletes an item of the Trash forever with its files', function (): void {
    $id = ($this->create)();
    $input = new ContentItemInput(ContentType::Banner, $id);
    $forceDelete = new ForceDeleteContentItem($this->items, $this->images);

    expect(fn () => $forceDelete->handle($input))->toThrow(EntityNotFound::class); // not in the Trash yet
    expect($this->files->paths())->toHaveCount(1);

    (new DeleteContentItem($this->items))->handle($input);
    $forceDelete->handle($input);

    expect($this->files->paths())->toBe([])
        ->and($this->items->findDeleted(ContentType::Banner, $id))->toBeNull();
    expect(fn () => $forceDelete->handle($input))->toThrow(EntityNotFound::class);
});
