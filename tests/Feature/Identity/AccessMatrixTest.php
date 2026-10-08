<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;

/*
| Columns: role (null = guest), URL, expected status. 302 for a guest means "sent to the login page".
*/
it('applies the access matrix', function (?string $role, string $uri, int $status): void {
    if ($role !== null) {
        $this->actingAs(User::factory()->state(['role' => UserRole::from($role)])->create());
    }

    $this->get($uri)->assertStatus($status);
})->with([
    // guest
    [null, '/admin', 302],
    [null, '/admin/admin-users', 302],
    [null, '/admin/customers', 302],
    [null, '/account', 302],
    // customer
    ['customer', '/admin', 403],
    ['customer', '/admin/admin-users', 403],
    ['customer', '/admin/customers', 403],
    ['customer', '/account', 200],
    // staff: orders area only
    ['staff', '/admin', 200],
    ['staff', '/admin/admin-users', 403],
    ['staff', '/admin/customers', 403],
    ['staff', '/account', 200],
    // admin: everything except admin users
    ['admin', '/admin', 200],
    ['admin', '/admin/admin-users', 403],
    ['admin', '/admin/customers', 200],
    ['admin', '/account', 200],
    // super admin: everything
    ['super_admin', '/admin', 200],
    ['super_admin', '/admin/admin-users', 200],
    ['super_admin', '/admin/customers', 200],
    ['super_admin', '/account', 200],
]);

it('sends a guest who opens the admin area to the login page', function (): void {
    $this->get('/admin')->assertRedirect('/login');
});

it('shows the branded 403 page to a customer', function (): void {
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden()->assertSee('Access denied');
});

it('hides sidebar sections the user cannot open', function (): void {
    $this->actingAs(User::factory()->staff()->create())->get('/admin')
        ->assertOk()->assertSee('Orders')->assertDontSee('Admin Users')->assertDontSee('Delivery Zones');

    $this->actingAs(User::factory()->admin()->create())->get('/admin')
        ->assertOk()->assertSee('Customers')->assertSee('Delivery Zones')->assertDontSee('Admin Users');

    $this->actingAs(User::factory()->superAdmin()->create())->get('/admin')
        ->assertOk()->assertSee('Admin Users');
});

it('refuses state-changing admin requests from the wrong roles', function (): void {
    $victim = User::factory()->create();

    $this->actingAs(User::factory()->staff()->create());
    $this->post("/admin/customers/{$victim->id}/block")->assertForbidden();
    $this->post("/admin/admin-users/{$victim->id}/block")->assertForbidden();

    expect($victim->fresh()->is_blocked)->toBeFalse();
});
