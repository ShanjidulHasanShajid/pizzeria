<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Controllers\Storefront;

use App\Modules\Identity\Presentation\Http\Requests\ForgotPasswordRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;

final class ForgotPasswordController
{
    public function create(): View
    {
        return view('identity::auth.forgot-password');
    }

    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
        }

        // The same answer whether or not the email exists, so this form cannot be used
        // to find out who has an account.
        return back()->with('status', 'If an account exists for that email, we have sent a password reset link.');
    }
}
