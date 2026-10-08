<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Controllers\Storefront;

use App\Modules\Identity\Application\UseCases\CheckLoginAllowed;
use App\Modules\Identity\Domain\Enums\Ability;
use App\Modules\Identity\Domain\Exceptions\AccountBlocked;
use App\Modules\Identity\Presentation\Http\Requests\LoginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class LoginController
{
    public function create(Request $request): View
    {
        $this->rememberPreviousPage($request);

        return view('identity::auth.login');
    }

    public function store(LoginRequest $request, CheckLoginAllowed $checkLoginAllowed): RedirectResponse
    {
        $request->authenticate();

        try {
            $checkLoginAllowed->handle((int) Auth::id());
        } catch (AccountBlocked $blocked) {
            Auth::logout();

            throw ValidationException::withMessages(['email' => $blocked->getMessage()]);
        }

        $request->session()->regenerate();

        // Staff, admins and super admins start in the admin area.
        if (Gate::allows(Ability::AccessAdmin->value)) {
            return redirect()->route('admin.dashboard');
        }

        // Customers go back to the page they came from (or the home page).
        return redirect()->intended(route('home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * Visitors who click "Sign in" on /menu should return to /menu afterwards.
     * Laravel stores "intended" by itself only when the auth middleware redirects.
     */
    private function rememberPreviousPage(Request $request): void
    {
        if ($request->session()->has('url.intended')) {
            return;
        }

        $previous = url()->previous();
        $path = (string) parse_url($previous, PHP_URL_PATH);
        $isThisSite = str_starts_with($previous, $request->getSchemeAndHttpHost());
        $isAuthPage = Str::startsWith($path, ['/login', '/register', '/forgot-password', '/reset-password', '/logout']);

        if ($isThisSite && ! $isAuthPage) {
            $request->session()->put('url.intended', $previous);
        }
    }
}
