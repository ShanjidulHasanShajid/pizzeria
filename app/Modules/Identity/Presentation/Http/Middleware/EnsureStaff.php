<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Middleware;

use App\Modules\Identity\Domain\Enums\Ability;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets staff, admins and super admins through. Everyone else gets the branded 403 page.
 * Runs after "auth", so guests were already sent to the login page.
 */
final class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Gate::allows(Ability::AccessAdmin->value), 403);

        return $next($request);
    }
}
