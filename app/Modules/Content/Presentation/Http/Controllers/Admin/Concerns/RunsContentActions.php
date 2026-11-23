<?php

declare(strict_types=1);

namespace App\Modules\Content\Presentation\Http\Controllers\Admin\Concerns;

use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Presentation\Support\ValidationFailedTranslator;
use Closure;

/**
 * A broken rule goes back to the form with the error under its field; an unknown id is a 404 page.
 */
trait RunsContentActions
{
    /**
     * @template TResult
     *
     * @param  Closure(): TResult  $action
     * @return TResult
     */
    private function runAction(Closure $action): mixed
    {
        try {
            return $action();
        } catch (ValidationFailed $failure) {
            throw (new ValidationFailedTranslator)->toValidationException($failure);
        } catch (EntityNotFound) {
            abort(404);
        }
    }
}
