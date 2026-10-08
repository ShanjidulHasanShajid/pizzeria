<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Middleware;

use App\Modules\Identity\Application\UseCases\CheckLoginAllowed;
use App\Modules\Identity\Domain\Exceptions\AccountBlocked;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs a user out the moment an admin blocks them, even if they are in the middle of a session.
 * It costs one primary-key lookup per request of a logged-in user.
 */
final class EnsureUserIsNotBlocked
{
    public function __construct(private readonly CheckLoginAllowed $checkLoginAllowed) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            try {
                $this->checkLoginAllowed->handle((int) Auth::id());
            } catch (AccountBlocked $blocked) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors(['email' => $blocked->getMessage()]);
            }
        }

        return $next($request);
    }
}
