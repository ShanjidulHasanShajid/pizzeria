<?php

declare(strict_types=1);

namespace App\Modules\Customization\Presentation\Http\Controllers\Admin;

use App\Modules\Customization\Application\DTOs\OptionItemInput;
use App\Modules\Customization\Application\DTOs\TrashedOptionInput;
use App\Modules\Customization\Application\Queries\AdminOptionQuery;
use App\Modules\Customization\Application\UseCases\Options\ForceDeleteOption;
use App\Modules\Customization\Application\UseCases\Options\RestoreOption;
use App\Modules\Customization\Application\UseCases\Options\ToggleOptionAvailable;
use App\Modules\Customization\Presentation\Http\Controllers\Admin\Concerns\RunsCustomizationActions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * "Sold out today" for all options on one page (routes require "manage-availability": staff too),
 * and the Trash actions of single options (routes require "manage-catalog";
 * "delete forever" also requires "force-delete").
 */
final class OptionController
{
    use RunsCustomizationActions;

    public function availability(AdminOptionQuery $query): View
    {
        return view('customization::admin.option-groups.availability', ['groups' => $query->groups()]);
    }

    public function toggle(int $group, int $option, ToggleOptionAvailable $toggleAvailable): RedirectResponse
    {
        return $this->runListAction(
            fn () => $toggleAvailable->handle(new OptionItemInput($group, $option)),
            'Option switched.',
        );
    }

    public function restore(int $option, RestoreOption $restoreOption): RedirectResponse
    {
        return $this->runListAction(
            fn () => $restoreOption->handle(new TrashedOptionInput($option)),
            'Option restored.',
        );
    }

    public function forceDestroy(int $option, ForceDeleteOption $forceDelete): RedirectResponse
    {
        return $this->runListAction(
            fn () => $forceDelete->handle(new TrashedOptionInput($option)),
            'Option deleted forever.',
        );
    }
}
