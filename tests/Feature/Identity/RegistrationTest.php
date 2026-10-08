<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;

function validRegistration(array $overrides = []): array
{
    return array_merge([
        'name' => 'Rahim Uddin',
        'email' => 'Rahim@Example.com',
        'phone' => '+880 1712-345678',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ], $overrides);
}

it('shows the registration page', function (): void {
    $this->get('/register')->assertOk()->assertSee('Create your account');
});

it('registers a customer with a normalized phone and logs them in', function (): void {
    $this->post('/register', validRegistration())->assertRedirect('/');

    $user = User::query()->where('email', 'rahim@example.com')->firstOrFail();

    expect($user->phone)->toBe('01712345678')
        ->and($user->role)->toBe(UserRole::Customer)
        ->and($user->is_blocked)->toBeFalse();
    $this->assertAuthenticatedAs($user);
});

it('stores the password hashed', function (): void {
    $this->post('/register', validRegistration());

    expect(User::query()->firstOrFail()->password)->not->toBe('secret123');
});

it('ignores a role sent by the browser', function (): void {
    $this->post('/register', validRegistration(['role' => 'super_admin', 'is_blocked' => '1']));

    $user = User::query()->firstOrFail();

    expect($user->role)->toBe(UserRole::Customer)->and($user->is_blocked)->toBeFalse();
});

it('rejects a phone that already exists, even written differently', function (): void {
    User::factory()->create(['phone' => '01712345678']);

    $this->post('/register', validRegistration(['email' => 'other@example.com', 'phone' => '8801712345678']))
        ->assertSessionHasErrors('phone');

    expect(User::query()->count())->toBe(1);
});

it('rejects an email that already exists', function (): void {
    User::factory()->create(['email' => 'rahim@example.com']);

    $this->post('/register', validRegistration(['phone' => '01811111111']))->assertSessionHasErrors('email');
});

it('rejects an invalid phone number', function (): void {
    $this->post('/register', validRegistration(['phone' => '12345']))->assertSessionHasErrors('phone');
    $this->assertGuest();
});

it('rejects a weak or unconfirmed password', function (): void {
    $this->post('/register', validRegistration(['password' => 'short', 'password_confirmation' => 'short']))
        ->assertSessionHasErrors('password');
    $this->post('/register', validRegistration(['password_confirmation' => 'different123']))
        ->assertSessionHasErrors('password');
});

it('limits how fast accounts can be registered', function (): void {
    foreach (range(1, 5) as $attempt) {
        $this->post('/register', validRegistration(['phone' => 'bad']));
    }

    $this->post('/register', validRegistration())->assertStatus(429);
});

it('redirects a logged-in user away from the registration page', function (): void {
    $this->actingAs(User::factory()->create())->get('/register')->assertRedirect('/');
});
