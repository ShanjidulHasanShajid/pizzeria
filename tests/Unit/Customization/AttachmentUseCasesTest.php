<?php

declare(strict_types=1);

use App\Modules\Customization\Application\DTOs\AttachOptionGroupInput;
use App\Modules\Customization\Application\DTOs\BulkAttachOptionGroupInput;
use App\Modules\Customization\Application\DTOs\BulkAttachResult;
use App\Modules\Customization\Application\DTOs\DetachOptionGroupInput;
use App\Modules\Customization\Application\DTOs\ReorderAttachedGroupsInput;
use App\Modules\Customization\Application\DTOs\SelectionRulesInput;
use App\Modules\Customization\Application\DTOs\UpdateAttachedRulesInput;
use App\Modules\Customization\Application\Services\OptionGroupAttacher;
use App\Modules\Customization\Application\UseCases\Attachments\AttachOptionGroup;
use App\Modules\Customization\Application\UseCases\Attachments\BulkAttachOptionGroup;
use App\Modules\Customization\Application\UseCases\Attachments\DetachOptionGroup;
use App\Modules\Customization\Application\UseCases\Attachments\ReorderAttachedGroups;
use App\Modules\Customization\Application\UseCases\Attachments\UpdateAttachedRules;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Entities\ProductOptionGroup;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Tests\Unit\Customization\Fakes\InMemoryCustomizableProducts;
use Tests\Unit\Customization\Fakes\InMemoryOptionGroupRepository;
use Tests\Unit\Customization\Fakes\InMemoryProductOptionGroupRepository;
use Tests\Unit\Identity\PassThroughTransactionManager;

/** Pick exactly one. */
function exactlyOneInput(): SelectionRulesInput
{
    return new SelectionRulesInput(isRequired: true, minimum: 1, maximum: 1, freeCount: 0);
}

/*
| Category 5 has the products 10 and 11. Group 1 is "Crust", group 2 is "Dips".
*/
beforeEach(function (): void {
    $products = new InMemoryCustomizableProducts([5 => [10, 11], 6 => []]);
    $groups = new InMemoryOptionGroupRepository;
    $groups->save(OptionGroup::create('Crust', 'Crust', SortOrder::first()));
    $groups->save(OptionGroup::create('Dips', 'Dips', SortOrder::fromInt(20)));
    $this->attachments = new InMemoryProductOptionGroupRepository;
    $this->transactions = new PassThroughTransactionManager;
    $attacher = new OptionGroupAttacher($this->attachments);

    $this->attach = fn (int $productId, int $groupId, ?SelectionRulesInput $rules = null): int => (new AttachOptionGroup($products, $groups, $attacher, $this->transactions))
        ->handle(new AttachOptionGroupInput($productId, $groupId, $rules ?? exactlyOneInput()));
    $this->bulkAttach = fn (int $categoryId, int $groupId): BulkAttachResult => (new BulkAttachOptionGroup($products, $groups, $attacher, $this->transactions))
        ->handle(new BulkAttachOptionGroupInput($categoryId, $groupId, exactlyOneInput()));
    $this->groupIds = fn (int $productId): array => array_map(
        fn (ProductOptionGroup $attachment): int => $attachment->optionGroupId(),
        $this->attachments->forProduct($productId),
    );
});

it('attaches groups at the end of the product list, with their rules', function (): void {
    ($this->attach)(10, 2);
    $id = ($this->attach)(10, 1, new SelectionRulesInput(false, 0, 3, 1));

    $crust = $this->attachments->find($id);

    expect(($this->groupIds)(10))->toBe([2, 1])
        ->and($crust->sort()->value())->toBe(20)
        ->and($crust->rules()->maximum())->toBe(3)
        ->and($crust->rules()->freeCount())->toBe(1);
});

it('refuses to attach the same group twice', function (): void {
    ($this->attach)(10, 1);

    ($this->attach)(10, 1);
})->throws(ValidationFailed::class);

it('refuses an unknown group or product', function (): void {
    expect(fn () => ($this->attach)(10, 99))->toThrow(ValidationFailed::class);
    expect(fn () => ($this->attach)(99, 1))->toThrow(EntityNotFound::class);
});

it('turns rules that cannot work into a form error', function (): void {
    try {
        ($this->attach)(10, 1, new SelectionRulesInput(true, 0, 1, 0));
    } catch (ValidationFailed $failure) {
        expect($failure->errors())->toHaveKey('rules')
            ->and(($this->groupIds)(10))->toBe([]);

        return;
    }

    throw new RuntimeException('Broken rules must be refused.');
});

it('changes the rules on one product only, and never through another product', function (): void {
    $onTen = ($this->attach)(10, 1);
    $onEleven = ($this->attach)(11, 1);
    $update = new UpdateAttachedRules($this->attachments, $this->transactions);
    $upToTwo = new SelectionRulesInput(false, 0, 2, 0);

    $update->handle(new UpdateAttachedRulesInput(10, $onTen, $upToTwo));

    expect($this->attachments->find($onTen)->rules()->maximum())->toBe(2)
        ->and($this->attachments->find($onEleven)->rules()->maximum())->toBe(1);
    expect(fn () => $update->handle(new UpdateAttachedRulesInput(10, $onEleven, $upToTwo)))->toThrow(EntityNotFound::class);
});

it('detaches a group, and never through another product', function (): void {
    $id = ($this->attach)(10, 1);
    $detach = new DetachOptionGroup($this->attachments, $this->transactions);

    expect(fn () => $detach->handle(new DetachOptionGroupInput(11, $id)))->toThrow(EntityNotFound::class);

    $detach->handle(new DetachOptionGroupInput(10, $id));

    expect(($this->groupIds)(10))->toBe([]);
});

it('saves a new order of the attached groups', function (): void {
    $crust = ($this->attach)(10, 1);
    $dips = ($this->attach)(10, 2);

    (new ReorderAttachedGroups($this->attachments, $this->transactions))->handle(new ReorderAttachedGroupsInput(10, [$dips, $crust]));

    expect(($this->groupIds)(10))->toBe([2, 1]);
});

it('refuses an order that misses a group or names one of another product', function (): void {
    $crust = ($this->attach)(10, 1);
    ($this->attach)(10, 2);
    $other = ($this->attach)(11, 2);
    $reorder = new ReorderAttachedGroups($this->attachments, $this->transactions);

    expect(fn () => $reorder->handle(new ReorderAttachedGroupsInput(10, [$crust])))->toThrow(ValidationFailed::class);
    expect(fn () => $reorder->handle(new ReorderAttachedGroupsInput(10, [$crust, $other])))->toThrow(ValidationFailed::class);
});

it('attaches a group to every product of a category and leaves existing rules alone', function (): void {
    $own = ($this->attach)(10, 1, new SelectionRulesInput(false, 0, 4, 0));

    $result = ($this->bulkAttach)(5, 1);

    expect($result->attached)->toBe(1)
        ->and($result->alreadyAttached)->toBe(1)
        ->and(($this->groupIds)(11))->toBe([1])
        ->and($this->attachments->find($own)->rules()->maximum())->toBe(4);
});

it('refuses a bulk attach with an unknown category or group, and accepts an empty category', function (): void {
    expect(fn () => ($this->bulkAttach)(99, 1))->toThrow(ValidationFailed::class);
    expect(fn () => ($this->bulkAttach)(5, 99))->toThrow(ValidationFailed::class);
    expect(($this->bulkAttach)(6, 1)->attached)->toBe(0);
});
