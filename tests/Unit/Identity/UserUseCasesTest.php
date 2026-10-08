<?php

declare(strict_types=1);

use App\Modules\Identity\Application\DTOs\ChangeRoleData;
use App\Modules\Identity\Application\DTOs\CreateUserData;
use App\Modules\Identity\Application\DTOs\RegisterCustomerData;
use App\Modules\Identity\Application\DTOs\UserActionData;
use App\Modules\Identity\Application\UseCases\BlockUser;
use App\Modules\Identity\Application\UseCases\ChangeUserRole;
use App\Modules\Identity\Application\UseCases\CheckLoginAllowed;
use App\Modules\Identity\Application\UseCases\CreateUser;
use App\Modules\Identity\Application\UseCases\DeleteUser;
use App\Modules\Identity\Application\UseCases\RegisterCustomer;
use App\Modules\Identity\Application\UseCases\RestoreUser;
use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Domain\Exceptions\AccountBlocked;
use App\Modules\Identity\Domain\Exceptions\UserProtected;
use App\Modules\Identity\Domain\Services\UserProtection;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use Tests\Unit\Identity\InMemoryUserRepository;
use Tests\Unit\Identity\PassThroughTransactionManager;

beforeEach(function (): void {
    $this->users = new InMemoryUserRepository;
    $this->transactions = new PassThroughTransactionManager;
    $this->protection = new UserProtection;
});

it('registers a customer with a normalized phone and the customer role', function (): void {
    $id = (new RegisterCustomer($this->users))->handle(
        new RegisterCustomerData('  Rahim  ', 'RAHIM@Example.com', '+880 1712-345678', 'secret123'),
    );

    $saved = $this->users->find($id);

    expect($saved->name)->toBe('Rahim')
        ->and($saved->email)->toBe('rahim@example.com')
        ->and($saved->phone)->toBe('01712345678')
        ->and($saved->role)->toBe(UserRole::Customer);
});

it('rejects an invalid phone when registering', function (): void {
    (new RegisterCustomer($this->users))->handle(new RegisterCustomerData('Rahim', 'r@example.com', '12345', 'secret123'));
})->throws(InvalidValue::class);

it('creates staff and admin accounts only', function (): void {
    $create = new CreateUser($this->users);

    $id = $create->handle(new CreateUserData('Sara', 'sara@example.com', null, 'secret123', UserRole::Staff));
    expect($this->users->find($id)->role)->toBe(UserRole::Staff);

    $create->handle(new CreateUserData('Eve', 'eve@example.com', null, 'secret123', UserRole::SuperAdmin));
})->throws(ValidationFailed::class);

it('blocks another user', function (): void {
    $actor = $this->users->add(UserRole::SuperAdmin);
    $target = $this->users->add(UserRole::Customer);

    (new BlockUser($this->users, $this->protection, $this->transactions))->handle(new UserActionData($actor->id, $target->id));

    expect($this->users->find($target->id)->isBlocked)->toBeTrue();
});

it('refuses to let a super admin block themselves and changes nothing', function (): void {
    $me = $this->users->add(UserRole::SuperAdmin);
    $this->users->add(UserRole::SuperAdmin);

    try {
        (new BlockUser($this->users, $this->protection, $this->transactions))->handle(new UserActionData($me->id, $me->id));
    } catch (UserProtected) {
        expect($this->users->find($me->id)->isBlocked)->toBeFalse();

        return;
    }

    $this->fail('Expected UserProtected');
});

it('refuses to delete the last super admin even from another account', function (): void {
    $last = $this->users->add(UserRole::SuperAdmin);
    $admin = $this->users->add(UserRole::Admin);

    (new DeleteUser($this->users, $this->protection, $this->transactions))->handle(new UserActionData($admin->id, $last->id));
})->throws(UserProtected::class, 'You cannot delete the last super admin.');

it('refuses to demote the last super admin', function (): void {
    $last = $this->users->add(UserRole::SuperAdmin);
    $other = $this->users->add(UserRole::Admin);

    (new ChangeUserRole($this->users, $this->protection, $this->transactions))
        ->handle(new ChangeRoleData($other->id, $last->id, UserRole::Admin));
})->throws(UserProtected::class);

it('changes a role', function (): void {
    $actor = $this->users->add(UserRole::SuperAdmin);
    $target = $this->users->add(UserRole::Staff);

    (new ChangeUserRole($this->users, $this->protection, $this->transactions))
        ->handle(new ChangeRoleData($actor->id, $target->id, UserRole::Admin));

    expect($this->users->find($target->id)->role)->toBe(UserRole::Admin);
});

it('soft deletes and restores a user', function (): void {
    $actor = $this->users->add(UserRole::SuperAdmin);
    $target = $this->users->add(UserRole::Staff);

    (new DeleteUser($this->users, $this->protection, $this->transactions))->handle(new UserActionData($actor->id, $target->id));
    expect($this->users->find($target->id)->isDeleted)->toBeTrue();

    (new RestoreUser($this->users))->handle(new UserActionData($actor->id, $target->id));
    expect($this->users->find($target->id)->isDeleted)->toBeFalse();
});

it('reports a missing user', function (): void {
    (new BlockUser($this->users, $this->protection, $this->transactions))->handle(new UserActionData(1, 999));
})->throws(EntityNotFound::class);

it('allows an active user to log in and refuses a blocked or deleted one', function (): void {
    $check = new CheckLoginAllowed($this->users);
    $ok = $this->users->add(UserRole::Customer);
    $blocked = $this->users->add(UserRole::Customer, blocked: true);
    $deleted = $this->users->add(UserRole::Customer, deleted: true);

    $check->handle($ok->id);
    expect(fn () => $check->handle($blocked->id))->toThrow(AccountBlocked::class)
        ->and(fn () => $check->handle($deleted->id))->toThrow(AccountBlocked::class)
        ->and(fn () => $check->handle(12345))->toThrow(AccountBlocked::class);
});
