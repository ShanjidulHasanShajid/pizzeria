<?php

declare(strict_types=1);

namespace App\Modules\Customization\Presentation\Http\Controllers\Admin\Concerns;

use App\Modules\Customization\Domain\Exceptions\OptionGroupInUse;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Presentation\Support\ValidationFailedTranslator;
use Closure;
use Illuminate\Http\RedirectResponse;

/**
 * The same three answers for every option admin action:
 * - a broken form rule goes back to the form with the error under the field,
 * - "still in use" goes back with a red message at the top,
 * - an unknown id is a 404 page.
 */
trait RunsCustomizationActions
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
     * For buttons on a list (delete, restore): back to the page with a flash message.
     */
    private function runListAction(Closure $action, string $successMessage): RedirectResponse
    {
        try {
            $action();
        } catch (OptionGroupInUse $inUse) {
            return back()->with('error', $inUse->getMessage());
        } catch (EntityNotFound) {
            abort(404);
        }

        return back()->with('success', $successMessage);
    }
}
