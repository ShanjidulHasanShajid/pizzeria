<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

it('shows the forgot password page', function (): void {
    $this->get('/forgot-password')->assertOk();
});

it('emails a reset link to a known address', function (): void {
    Notification::fake();
    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);
});

it('gives the same answer for an unknown address and sends nothing', function (): void {
    Notification::fake();

    $this->post('/forgot-password', ['email' => 'nobody@example.com'])
        ->assertSessionHas('status', 'If an account exists for that email, we have sent a password reset link.');

    Notification::assertNothingSent();
});

it('resets the password with a valid token', function (): void {
    Notification::fake();
    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
        $this->get('/reset-password/'.$notification->token)->assertOk();

        $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'brand-new-pass1',
            'password_confirmation' => 'brand-new-pass1',
        ])->assertRedirect('/login');

        expect(Hash::check('brand-new-pass1', $user->fresh()->password))->toBeTrue();

        return true;
    });
});

it('rejects an invalid token', function (): void {
    $user = User::factory()->create();

    $this->post('/reset-password', [
        'token' => 'not-a-real-token',
        'email' => $user->email,
        'password' => 'brand-new-pass1',
        'password_confirmation' => 'brand-new-pass1',
    ])->assertSessionHasErrors('email');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});
