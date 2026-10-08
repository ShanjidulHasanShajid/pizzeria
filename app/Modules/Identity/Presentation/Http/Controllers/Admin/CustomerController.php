<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Controllers\Admin;

use App\Modules\Identity\Application\DTOs\UserActionData;
use App\Modules\Identity\Application\DTOs\UserListFilter;
use App\Modules\Identity\Application\Queries\UserQueries;
use App\Modules\Identity\Application\UseCases\BlockUser;
use App\Modules\Identity\Application\UseCases\UnblockUser;
use App\Modules\Identity\Domain\Enums\UserRole;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

/**
 * Read-only customer list with block and unblock. Routes require the "manage-customers" gate.
 */
final class CustomerController
{
    public function index(Request $request, UserQueries $queries): View
    {
        $filter = new UserListFilter(
            search: $request->string('q')->trim()->toString(),
            status: $request->string('status', 'all')->toString(),
            page: max(1, $request->integer('page', 1)),
        );

        $page = $queries->paginateCustomers($filter);

        return view('identity::admin.customers.index', [
            'customers' => new LengthAwarePaginator($page->items, $page->total, $page->perPage, $page->page, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]),
            'status' => $filter->status,
        ]);
    }

    public function block(int $user, UserQueries $queries, BlockUser $blockUser): RedirectResponse
    {
        $this->abortUnlessCustomer($user, $queries);

        $blockUser->handle(new UserActionData((int) Auth::id(), $user));

        return back()->with('success', 'Customer blocked.');
    }

    public function unblock(int $user, UserQueries $queries, UnblockUser $unblockUser): RedirectResponse
    {
        $this->abortUnlessCustomer($user, $queries);

        $unblockUser->handle(new UserActionData((int) Auth::id(), $user));

        return back()->with('success', 'Customer unblocked.');
    }

    /**
     * The customer screens must never be a back door to staff accounts:
     * an admin may block customers, but not a super admin.
     */
    private function abortUnlessCustomer(int $userId, UserQueries $queries): void
    {
        $user = $queries->find($userId);

        if ($user === null || $user->isDeleted || $user->role !== UserRole::Customer) {
            abort(404);
        }
    }
}
