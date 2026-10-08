<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Queries;

use App\Modules\Identity\Application\DTOs\UserListFilter;
use App\Modules\Identity\Application\DTOs\UserListItem;
use App\Modules\Identity\Application\DTOs\UserPage;
use App\Modules\Identity\Application\Queries\UserQueries;
use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;

final class EloquentUserQueries implements UserQueries
{
    public function find(int $id): ?UserListItem
    {
        $user = User::withTrashed()->find($id);

        return $user === null ? null : $this->toItem($user);
    }

    public function paginateStaff(UserListFilter $filter): UserPage
    {
        $query = User::query()->where('role', '!=', UserRole::Customer->value);

        return $this->paginate($query, $filter);
    }

    public function paginateCustomers(UserListFilter $filter): UserPage
    {
        $query = User::query()->where('role', UserRole::Customer->value);

        return $this->paginate($query, $filter);
    }

    /**
     * @param  Builder<User>  $query
     */
    private function paginate(Builder $query, UserListFilter $filter): UserPage
    {
        if ($filter->search !== '') {
            // Escape % and _ so a search for "50%" does not act as a wildcard.
            $like = '%'.addcslashes($filter->search, '%_\\').'%';
            $query->where(function (Builder $inner) use ($like): void {
                $inner->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            });
        }

        if ($filter->role !== null) {
            $query->where('role', $filter->role->value);
        }

        if ($filter->status === 'active') {
            $query->where('is_blocked', false);
        } elseif ($filter->status === 'blocked') {
            $query->where('is_blocked', true);
        } elseif ($filter->status === 'deleted') {
            $query->onlyTrashed();
        }

        $page = $query->orderByDesc('id')->paginate($filter->perPage, ['*'], 'page', $filter->page);

        return new UserPage(
            items: array_values($page->getCollection()->map(fn (User $user): UserListItem => $this->toItem($user))->all()),
            total: $page->total(),
            page: $page->currentPage(),
            perPage: $page->perPage(),
        );
    }

    private function toItem(User $user): UserListItem
    {
        return new UserListItem(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            phone: $user->phone,
            role: $user->role,
            isBlocked: $user->is_blocked,
            isDeleted: $user->trashed(),
            createdAt: $user->created_at?->toDateTimeImmutable() ?? new DateTimeImmutable,
        );
    }
}
