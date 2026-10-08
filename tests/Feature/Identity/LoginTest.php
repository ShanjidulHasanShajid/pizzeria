<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Models\User;

it('shows the login page', function (): void {
    $this->get('/login')->assertOk()->assertSee('Sign in');
});

it('sends customers to the home page after login', function (): void {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
});

it('sends customers back to the page they were trying to open', function (): void {
    $user = User::factory()->create();

    $this->get('/account')->assertRedirect('/login');
    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/account');
});

it('sends staff, admins and super admins to the admin dashboard', function (string $state): void {
    $user = User::factory()->{$state}()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/admin');
})->with(['staff', 'admin', 'superAdmin']);

it('rejects a wrong password', function (): void {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('refuses a blocked user with a clear message', function (): void {
    $user = User::factory()->blocked()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'Your account has been blocked. Please contact the restaurant for help.']);
    $this->assertGuest();
});

it('does not reveal that an account is blocked when the password is wrong', function (): void {
    $user = User::factory()->blocked()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);

    expect(session('errors')->first('email'))->not->toContain('blocked');
});

it('refuses a deleted user', function (): void {
    $user = User::factory()->create();
    $user->delete();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('logs out a user who is blocked while signed in', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $user->is_blocked = true;
    $user->save();

    $this->get('/')->assertRedirect('/login');
    $this->assertGuest();
});

it('locks the login after five failed attempts', function (): void {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
    }

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toContain('Too many');
    $this->assertGuest();
});

it('logs out with a POST request', function (): void {
    $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect('/');

    $this->assertGuest();
});

it('does not log out with a GET request', function (): void {
    $this->actingAs(User::factory()->create())->get('/logout')->assertStatus(405);
});
