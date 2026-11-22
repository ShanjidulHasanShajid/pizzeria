<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Controllers\Admin\Concerns;

use App\Modules\Catalog\Domain\Exceptions\CatalogItemInUse;
use App\Modules\Catalog\Domain\Exceptions\RestoreBlocked;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Presentation\Support\ValidationFailedTranslator;
use Closure;
use Illuminate\Http\RedirectResponse;

/**
 * The same three answers for every catalog admin action, written once:
 * - a broken form rule (ValidationFailed) goes back to the form with the error under the field,
 * - "still in use" or "restore the parent first" goes back with a red message at the top,
 * - an unknown id is a 404 page.
 */
trait RunsCatalogActions
{
    /**
     * For forms (create, update). Returns what the use case returns.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $action
     * @return TResult
     */
    private function runFormAction(Closure $action): mixed
    {
        try {
            return $action();
        } catch (ValidationFailed $failure) {
            throw (new ValidationFailedTranslator)->toValidationException($failure);
        } catch (EntityNotFound) {
            abort(404);
        }
    }

    /**
     * For buttons on a list (delete, restore, toggle): back to the page with a flash message.
     */
    private function runListAction(Closure $action, string $successMessage): RedirectResponse
    {
        try {
            $action();
        } catch (CatalogItemInUse|RestoreBlocked $refused) {
            return back()->with('error', $refused->getMessage());
        } catch (EntityNotFound) {
            abort(404);
        }

        return back()->with('success', $successMessage);
    }
}
