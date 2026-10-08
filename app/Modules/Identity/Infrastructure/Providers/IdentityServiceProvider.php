<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Providers;

use App\Modules\Identity\Application\Queries\UserQueries;
use App\Modules\Identity\Domain\Enums\Ability;
use App\Modules\Identity\Domain\Repositories\UserRepository;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use App\Modules\Identity\Infrastructure\Persistence\Repositories\EloquentUserRepository;
use App\Modules\Identity\Infrastructure\Queries\EloquentUserQueries;
use App\Modules\Shared\Infrastructure\Providers\ModuleServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;

final class IdentityServiceProvider extends ModuleServiceProvider
{
    /**
     * Interface => implementation pairs for this module.
     * Laravel registers this array automatically.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        UserRepository::class => EloquentUserRepository::class,
        UserQueries::class => EloquentUserQueries::class,
    ];

    public function boot(): void
    {
        parent::boot();

        $this->defineGates();
        $this->defineRateLimiters();

        // Used wherever a form says Password::defaults().
        Password::defaults(fn (): Password => Password::min(8)->letters()->numbers());
    }

    protected function moduleName(): string
    {
        return 'Identity';
    }

    /**
     * One gate per Ability. A gate is just a yes/no question Laravel can ask:
     * Gate::allows('manage-catalog'), @can('manage-catalog'), middleware 'can:manage-catalog'.
     */
    private function defineGates(): void
    {
        foreach (Ability::cases() as $ability) {
            Gate::define(
                $ability->value,
                static fn (Authenticatable $user): bool => $user instanceof User && $user->role->can($ability),
            );
        }
    }

    private function defineRateLimiters(): void
    {
        // Registration and password-reset requests: 5 per minute per IP address.
        RateLimiter::for('register', fn (Request $request): Limit => Limit::perMinute(5)->by((string) $request->ip()));
        RateLimiter::for('password-reset', fn (Request $request): Limit => Limit::perMinute(5)->by((string) $request->ip()));
    }
}
