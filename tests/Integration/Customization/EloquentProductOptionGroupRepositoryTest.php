<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Entities\ProductOptionGroup;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Customization\Domain\Repositories\ProductOptionGroupRepository;
use App\Modules\Customization\Domain\ValueObjects\SelectionRules;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Illuminate\Database\UniqueConstraintViolationException;

beforeEach(function (): void {
    $this->repository = app(ProductOptionGroupRepository::class);
    $this->product = ProductModel::factory()->create();
    $groups = app(OptionGroupRepository::class);
    $this->crust = $groups->save(OptionGroup::create('Crusts', 'Crust', SortOrder::fromInt(10)));
    $this->dips = $groups->save(OptionGroup::create('Dips', 'Dips', SortOrder::fromInt(20)));
});

it('saves an attachment with its rules and finds it again', function (): void {
    $id = $this->repository->save(ProductOptionGroup::attach(
        $this->product->id, $this->dips, SelectionRules::of(false, 2, 3, 1), SortOrder::fromInt(20),
    ));

    $attachment = $this->repository->find($id);

    expect($attachment->id())->toBe($id)
        ->and($attachment->productId())->toBe($this->product->id)
        ->and($attachment->optionGroupId())->toBe($this->dips)
        ->and($attachment->rules()->equals(SelectionRules::of(false, 2, 3, 1)))->toBeTrue()
        ->and($attachment->sort()->value())->toBe(20)
        ->and($this->repository->find(424242))->toBeNull();
});

it('lists what is attached to one product, in sort order', function (): void {
    $other = ProductModel::factory()->create();
    $this->repository->save(ProductOptionGroup::attach($this->product->id, $this->dips, SelectionRules::upTo(2), SortOrder::fromInt(20)));
    $this->repository->save(ProductOptionGroup::attach($this->product->id, $this->crust, SelectionRules::exactlyOne(), SortOrder::fromInt(10)));
    $this->repository->save(ProductOptionGroup::attach($other->id, $this->dips, SelectionRules::upTo(2), SortOrder::fromInt(10)));

    $groupIds = array_map(
        fn (ProductOptionGroup $attachment): int => $attachment->optionGroupId(),
        $this->repository->forProduct($this->product->id),
    );

    expect($groupIds)->toBe([$this->crust, $this->dips]);
});

it('updates the rules instead of adding a second row', function (): void {
    $id = $this->repository->save(ProductOptionGroup::attach($this->product->id, $this->crust, SelectionRules::exactlyOne(), SortOrder::first()));
    $attachment = $this->repository->find($id);

    $attachment->changeRules(SelectionRules::upTo(1));
    $this->repository->save($attachment);

    expect($this->repository->find($id)->rules()->isRequired())->toBeFalse();
    $this->assertDatabaseCount('product_option_group', 1);
});

it('detaches a group without touching the group or the product', function (): void {
    $id = $this->repository->save(ProductOptionGroup::attach($this->product->id, $this->crust, SelectionRules::exactlyOne(), SortOrder::first()));

    $this->repository->delete($id);

    expect($this->repository->forProduct($this->product->id))->toBe([]);
    $this->assertNotSoftDeleted('option_groups', ['id' => $this->crust]);
    $this->assertNotSoftDeleted('products', ['id' => $this->product->id]);
});

it('knows when a group is attached, also to a product in the Trash', function (): void {
    $this->repository->save(ProductOptionGroup::attach($this->product->id, $this->crust, SelectionRules::exactlyOne(), SortOrder::first()));
    $this->product->delete();

    expect($this->repository->isGroupAttached($this->crust))->toBeTrue()
        ->and($this->repository->isGroupAttached($this->dips))->toBeFalse();
});

it('cannot attach the same group to a product twice', function (): void {
    $attach = fn (): int => $this->repository->save(ProductOptionGroup::attach($this->product->id, $this->crust, SelectionRules::exactlyOne(), SortOrder::first()));
    $attach();

    expect($attach)->toThrow(UniqueConstraintViolationException::class);
});
