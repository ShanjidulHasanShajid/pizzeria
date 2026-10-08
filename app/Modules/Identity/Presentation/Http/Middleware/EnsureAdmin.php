<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Middleware;

use App\Modules\Identity\Domain\Enums\Ability;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admins and super admins only. Used for every admin section except orders.
 */
final class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Gate::allows(Ability::AccessAdminSections->value), 403);

        return $next($request);
    }
}
