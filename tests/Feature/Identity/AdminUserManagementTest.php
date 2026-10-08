<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;

beforeEach(function (): void {
    $this->super = User::factory()->superAdmin()->create();
    $this->actingAs($this->super);
});

it('lists staff accounts but not customers', function (): void {
    User::factory()->staff()->create(['name' => 'Visible Staff']);
    User::factory()->create(['name' => 'Hidden Customer']);

    $this->get('/admin/admin-users')->assertSee('Visible Staff')->assertDontSee('Hidden Customer');
});

it('filters by role and searches by name', function (): void {
    User::factory()->staff()->create(['name' => 'Sara Staff']);
    User::factory()->admin()->create(['name' => 'Adam Admin']);

    $this->get('/admin/admin-users?role=staff')->assertSee('Sara Staff')->assertDontSee('Adam Admin');
    $this->get('/admin/admin-users?q=Adam')->assertSee('Adam Admin')->assertDontSee('Sara Staff');
});

it('creates a staff user', function (): void {
    $this->post('/admin/admin-users', [
        'name' => 'New Staff',
        'email' => 'new.staff@example.com',
        'phone' => '01811111111',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'role' => 'staff',
    ])->assertRedirect('/admin/admin-users');

    expect(User::query()->where('email', 'new.staff@example.com')->firstOrFail()->role)->toBe(UserRole::Staff);
});

it('cannot create a user with the customer or super admin role from the create form', function (string $role): void {
    $this->post('/admin/admin-users', [
        'name' => 'Nope', 'email' => 'nope@example.com', 'password' => 'secret123', 'password_confirmation' => 'secret123', 'role' => $role,
    ])->assertSessionHasErrors('role');
})->with(['customer', 'super_admin']);

it('updates a user and keeps the password when the field is empty', function (): void {
    $staff = User::factory()->staff()->create();
    $oldHash = $staff->password;

    $this->put("/admin/admin-users/{$staff->id}", [
        'name' => 'Renamed', 'email' => $staff->email, 'phone' => '', 'password' => '', 'password_confirmation' => '',
    ])->assertSessionHas('success');

    expect($staff->fresh()->name)->toBe('Renamed')->and($staff->fresh()->password)->toBe($oldHash);
});

it('changes a role', function (): void {
    $staff = User::factory()->staff()->create();

    $this->patch("/admin/admin-users/{$staff->id}/role", ['role' => 'admin'])->assertSessionHas('success');

    expect($staff->fresh()->role)->toBe(UserRole::Admin);
});

it('blocks, unblocks, deletes and restores a user', function (): void {
    $staff = User::factory()->staff()->create();

    $this->post("/admin/admin-users/{$staff->id}/block")->assertSessionHas('success');
    expect($staff->fresh()->is_blocked)->toBeTrue();

    $this->post("/admin/admin-users/{$staff->id}/unblock");
    expect($staff->fresh()->is_blocked)->toBeFalse();

    $this->delete("/admin/admin-users/{$staff->id}");
    expect(User::withTrashed()->find($staff->id)->trashed())->toBeTrue();

    $this->post("/admin/admin-users/{$staff->id}/restore");
    expect(User::query()->find($staff->id))->not->toBeNull();
});

it('does not let a super admin block, delete or demote themselves', function (): void {
    User::factory()->superAdmin()->create(); // a second one, so only the self rule can fire

    $this->post("/admin/admin-users/{$this->super->id}/block")->assertSessionHas('error');
    $this->delete("/admin/admin-users/{$this->super->id}")->assertSessionHas('error');
    $this->patch("/admin/admin-users/{$this->super->id}/role", ['role' => 'admin'])->assertSessionHas('error');

    $fresh = User::query()->find($this->super->id);
    expect($fresh)->not->toBeNull()
        ->and($fresh->is_blocked)->toBeFalse()
        ->and($fresh->role)->toBe(UserRole::SuperAdmin);
});

it('can block another super admin while two are active', function (): void {
    $other = User::factory()->superAdmin()->create();

    $this->post("/admin/admin-users/{$other->id}/block")->assertSessionHas('success');

    expect($other->fresh()->is_blocked)->toBeTrue();
});

it('lets a deleted user fail to log in until restored', function (): void {
    $staff = User::factory()->staff()->create();
    $this->delete("/admin/admin-users/{$staff->id}");
    auth()->logout();

    $this->post('/login', ['email' => $staff->email, 'password' => 'password'])->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('returns 404 for an unknown user id', function (): void {
    $this->get('/admin/admin-users/99999/edit')->assertNotFound();
    $this->post('/admin/admin-users/99999/block')->assertNotFound();
});
