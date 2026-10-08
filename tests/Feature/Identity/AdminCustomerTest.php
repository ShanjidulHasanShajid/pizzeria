<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Models\User;

beforeEach(function (): void {
    $this->actingAs(User::factory()->admin()->create());
});

it('lists customers with name, phone, email and joined date', function (): void {
    User::factory()->create(['name' => 'Nusrat Jahan', 'phone' => '01755555555', 'email' => 'nusrat@example.com']);
    User::factory()->staff()->create(['name' => 'Hidden Staff']);

    $this->get('/admin/customers')
        ->assertOk()
        ->assertSee('Nusrat Jahan')->assertSee('01755555555')->assertSee('nusrat@example.com')
        ->assertDontSee('Hidden Staff');
});

it('shows only blocked customers on the blocked tab', function (): void {
    User::factory()->blocked()->create(['name' => 'Blocked Bob']);
    User::factory()->create(['name' => 'Active Amy']);

    $this->get('/admin/customers?status=blocked')->assertSee('Blocked Bob')->assertDontSee('Active Amy');
});

it('blocks and unblocks a customer', function (): void {
    $customer = User::factory()->create();

    $this->post("/admin/customers/{$customer->id}/block")->assertSessionHas('success');
    expect($customer->fresh()->is_blocked)->toBeTrue();

    $this->post("/admin/customers/{$customer->id}/unblock");
    expect($customer->fresh()->is_blocked)->toBeFalse();
});

it('cannot be used to block a super admin or any staff account', function (): void {
    $super = User::factory()->superAdmin()->create();
    $staff = User::factory()->staff()->create();

    $this->post("/admin/customers/{$super->id}/block")->assertNotFound();
    $this->post("/admin/customers/{$staff->id}/block")->assertNotFound();

    expect($super->fresh()->is_blocked)->toBeFalse()->and($staff->fresh()->is_blocked)->toBeFalse();
});
