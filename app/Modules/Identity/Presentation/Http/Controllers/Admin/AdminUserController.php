<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Controllers\Admin;

use App\Modules\Identity\Application\DTOs\ChangeRoleData;
use App\Modules\Identity\Application\DTOs\CreateUserData;
use App\Modules\Identity\Application\DTOs\UpdateUserData;
use App\Modules\Identity\Application\DTOs\UserActionData;
use App\Modules\Identity\Application\DTOs\UserListFilter;
use App\Modules\Identity\Application\Queries\UserQueries;
use App\Modules\Identity\Application\UseCases\BlockUser;
use App\Modules\Identity\Application\UseCases\ChangeUserRole;
use App\Modules\Identity\Application\UseCases\CreateUser;
use App\Modules\Identity\Application\UseCases\DeleteUser;
use App\Modules\Identity\Application\UseCases\RestoreUser;
use App\Modules\Identity\Application\UseCases\UnblockUser;
use App\Modules\Identity\Application\UseCases\UpdateUser;
use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Domain\Exceptions\UserProtected;
use App\Modules\Identity\Presentation\Http\Requests\ChangeRoleRequest;
use App\Modules\Identity\Presentation\Http\Requests\StoreUserRequest;
use App\Modules\Identity\Presentation\Http\Requests\UpdateUserRequest;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

/**
 * Staff, admin and super admin accounts. Routes require the "manage-admin-users" gate.
 */
final class AdminUserController
{
    public function index(Request $request, UserQueries $queries): View
    {
        $filter = new UserListFilter(
            search: $request->string('q')->trim()->toString(),
            role: UserRole::tryFrom($request->string('role')->toString()),
            status: $request->string('status', 'all')->toString(),
            page: max(1, $request->integer('page', 1)),
        );

        $page = $queries->paginateStaff($filter);

        return view('identity::admin.users.index', [
            'users' => new LengthAwarePaginator($page->items, $page->total, $page->perPage, $page->page, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]),
            'status' => $filter->status,
            'role' => $filter->role,
            'roleOptions' => $this->staffRoleOptions(),
        ]);
    }

    public function create(): View
    {
        return view('identity::admin.users.create', ['createRoles' => $this->createRoleOptions()]);
    }

    public function store(StoreUserRequest $request, CreateUser $createUser): RedirectResponse
    {
        $data = $request->validated();

        $createUser->handle(new CreateUserData(
            name: (string) $data['name'],
            email: (string) $data['email'],
            phone: isset($data['phone']) ? (string) $data['phone'] : null,
            password: (string) $data['password'],
            role: UserRole::from((string) $data['role']),
        ));

        return redirect()->route('admin.admin-users.index')->with('success', 'User created.');
    }

    public function edit(int $user, UserQueries $queries): View
    {
        $item = $queries->find($user);

        if ($item === null || $item->isDeleted) {
            abort(404);
        }

        return view('identity::admin.users.edit', [
            'user' => $item,
            'allRoles' => collect(UserRole::cases())->mapWithKeys(fn (UserRole $role): array => [$role->value => $role->label()])->all(),
            'isMe' => $item->id === (int) Auth::id(),
        ]);
    }

    public function update(int $user, UpdateUserRequest $request, UpdateUser $updateUser): RedirectResponse
    {
        $data = $request->validated();

        return $this->attempt(fn () => $updateUser->handle(new UpdateUserData(
            userId: $user,
            name: (string) $data['name'],
            email: (string) $data['email'],
            phone: isset($data['phone']) ? (string) $data['phone'] : null,
            password: isset($data['password']) ? (string) $data['password'] : null,
        )), 'User saved.');
    }

    public function changeRole(int $user, ChangeRoleRequest $request, ChangeUserRole $changeUserRole): RedirectResponse
    {
        $role = UserRole::from($request->string('role')->toString());

        return $this->attempt(
            fn () => $changeUserRole->handle(new ChangeRoleData((int) Auth::id(), $user, $role)),
            'Role changed.',
        );
    }

    public function block(int $user, BlockUser $blockUser): RedirectResponse
    {
        return $this->attempt(fn () => $blockUser->handle(new UserActionData((int) Auth::id(), $user)), 'User blocked.');
    }

    public function unblock(int $user, UnblockUser $unblockUser): RedirectResponse
    {
        return $this->attempt(fn () => $unblockUser->handle(new UserActionData((int) Auth::id(), $user)), 'User unblocked.');
    }

    public function destroy(int $user, DeleteUser $deleteUser): RedirectResponse
    {
        return $this->attempt(fn () => $deleteUser->handle(new UserActionData((int) Auth::id(), $user)), 'User moved to the trash.');
    }

    public function restore(int $user, RestoreUser $restoreUser): RedirectResponse
    {
        return $this->attempt(fn () => $restoreUser->handle(new UserActionData((int) Auth::id(), $user)), 'User restored.');
    }

    /**
     * Runs a use case and turns the two expected failures into friendly responses.
     */
    private function attempt(Closure $action, string $successMessage): RedirectResponse
    {
        try {
            $action();
        } catch (UserProtected $protected) {
            return back()->with('error', $protected->getMessage());
        } catch (EntityNotFound) {
            abort(404);
        }

        return back()->with('success', $successMessage);
    }

    /**
     * @return array<string, string>
     */
    private function staffRoleOptions(): array
    {
        return [
            UserRole::Staff->value => UserRole::Staff->label(),
            UserRole::Admin->value => UserRole::Admin->label(),
            UserRole::SuperAdmin->value => UserRole::SuperAdmin->label(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function createRoleOptions(): array
    {
        return [
            UserRole::Staff->value => UserRole::Staff->label(),
            UserRole::Admin->value => UserRole::Admin->label(),
        ];
    }
}
