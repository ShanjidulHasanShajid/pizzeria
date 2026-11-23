<?php

declare(strict_types=1);

use App\Modules\Customization\Application\DTOs\CreateOptionGroupInput;
use App\Modules\Customization\Application\DTOs\OptionGroupItemInput;
use App\Modules\Customization\Application\DTOs\ReorderOptionGroupsInput;
use App\Modules\Customization\Application\DTOs\UpdateOptionGroupInput;
use App\Modules\Customization\Application\UseCases\OptionGroups\CreateOptionGroup;
use App\Modules\Customization\Application\UseCases\OptionGroups\DeleteOptionGroup;
use App\Modules\Customization\Application\UseCases\OptionGroups\ReorderOptionGroups;
use App\Modules\Customization\Application\UseCases\OptionGroups\RestoreOptionGroup;
use App\Modules\Customization\Application\UseCases\OptionGroups\UpdateOptionGroup;
use App\Modules\Customization\Domain\Entities\ProductOptionGroup;
use App\Modules\Customization\Domain\Exceptions\OptionGroupInUse;
use App\Modules\Customization\Domain\ValueObjects\SelectionRules;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Tests\Unit\Customization\Fakes\InMemoryOptionGroupRepository;
use Tests\Unit\Customization\Fakes\InMemoryProductOptionGroupRepository;
use Tests\Unit\Identity\PassThroughTransactionManager;

beforeEach(function (): void {
    $this->groups = new InMemoryOptionGroupRepository;
    $this->attachments = new InMemoryProductOptionGroupRepository;
    $this->transactions = new PassThroughTransactionManager;
    $this->createGroup = fn (string $name): int => (new CreateOptionGroup($this->groups))
        ->handle(new CreateOptionGroupInput($name, "Choose: {$name}", null));
    $this->deleteGroup = fn (int $id) => (new DeleteOptionGroup($this->groups, $this->attachments, $this->transactions))
        ->handle(new OptionGroupItemInput($id));
});

it('creates active groups at the end of the list', function (): void {
    $crust = ($this->createGroup)('Crust');
    $dips = ($this->createGroup)('Dips');

    expect($this->groups->find($crust)->sort()->value())->toBe(10)
        ->and($this->groups->find($dips)->sort()->value())->toBe(20)
        ->and($this->groups->find($dips)->displayTitle())->toBe('Choose: Dips')
        ->and($this->groups->find($dips)->isActive())->toBeTrue();
});

it('changes the texts and switches a group off', function (): void {
    $id = ($this->createGroup)('Crust');

    (new UpdateOptionGroup($this->groups, $this->transactions))
        ->handle(new UpdateOptionGroupInput($id, 'Pizza crusts', 'Your crust', 'Baked fresh.', isActive: false));

    $group = $this->groups->find($id);

    expect($group->name())->toBe('Pizza crusts')
        ->and($group->displayTitle())->toBe('Your crust')
        ->and($group->description())->toBe('Baked fresh.')
        ->and($group->isActive())->toBeFalse();
});

it('reports an unknown group when updating', function (): void {
    (new UpdateOptionGroup($this->groups, $this->transactions))
        ->handle(new UpdateOptionGroupInput(99, 'Crust', 'Crust', null, isActive: true));
})->throws(EntityNotFound::class);

it('moves an unattached group to the trash and restores it', function (): void {
    $id = ($this->createGroup)('Crust');

    ($this->deleteGroup)($id);
    expect($this->groups->find($id))->toBeNull();

    (new RestoreOptionGroup($this->groups))->handle(new OptionGroupItemInput($id));
    expect($this->groups->find($id))->not->toBeNull();
});

it('refuses to delete a group that is attached to a product', function (): void {
    $id = ($this->createGroup)('Crust');
    $this->attachments->save(ProductOptionGroup::attach(10, $id, SelectionRules::exactlyOne(), SortOrder::first()));

    expect(fn () => ($this->deleteGroup)($id))->toThrow(OptionGroupInUse::class, 'attached to products');
    expect($this->groups->find($id))->not->toBeNull();
});

it('saves a new order in steps of 10', function (): void {
    $crust = ($this->createGroup)('Crust');
    $dips = ($this->createGroup)('Dips');

    (new ReorderOptionGroups($this->groups, $this->transactions))->handle(new ReorderOptionGroupsInput([$dips, $crust]));

    expect($this->groups->find($dips)->sort()->value())->toBe(10)
        ->and($this->groups->find($crust)->sort()->value())->toBe(20);
});

it('refuses a new order with a group twice or an unknown group', function (): void {
    $crust = ($this->createGroup)('Crust');
    $reorder = new ReorderOptionGroups($this->groups, $this->transactions);

    expect(fn () => $reorder->handle(new ReorderOptionGroupsInput([$crust, $crust])))->toThrow(ValidationFailed::class);
    expect(fn () => $reorder->handle(new ReorderOptionGroupsInput([$crust, 99])))->toThrow(EntityNotFound::class);
});
