<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Controllers\Storefront;

use App\Modules\Identity\Application\DTOs\ChangePasswordData;
use App\Modules\Identity\Application\UseCases\ChangePassword;
use App\Modules\Identity\Presentation\Http\Requests\ResetPasswordRequest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

final class ResetPasswordController
{
    public function create(Request $request, string $token): View
    {
        return view('identity::auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function store(ResetPasswordRequest $request, ChangePassword $changePassword): RedirectResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request, $changePassword): void {
                $changePassword->handle(new ChangePasswordData(
                    (int) $user->getAuthIdentifier(),
                    $request->string('password')->toString(),
                ));

                event(new PasswordReset($user));
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __($status));
        }

        return back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }
}
