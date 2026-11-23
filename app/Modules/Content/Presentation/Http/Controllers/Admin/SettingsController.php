<?php

declare(strict_types=1);

namespace App\Modules\Content\Presentation\Http\Controllers\Admin;

use App\Modules\Content\Application\Queries\SettingsQuery;
use App\Modules\Content\Application\UseCases\Settings\UpdateSettingsGroup;
use App\Modules\Content\Domain\Enums\SettingGroup;
use App\Modules\Content\Presentation\Http\Requests\SettingsRequest;
use App\Modules\Shared\Application\Ports\FileStorage;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Presentation\Support\ValidationFailedTranslator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The settings screen, one tab per group. Routes require "manage-settings".
 */
final class SettingsController
{
    public function edit(Request $request, SettingsQuery $query, FileStorage $files): View
    {
        // An unknown ?tab= (typed by hand) shows the first tab.
        $group = SettingGroup::tryFrom($request->string('tab')->toString()) ?? SettingGroup::General;

        return view('content::admin.settings.edit', [
            'group' => $group,
            'groups' => SettingGroup::cases(),
            'fields' => $query->fields($group),
            'imageUrl' => fn (string $path): ?string => $path === '' ? null : $files->url($path),
        ]);
    }

    public function update(SettingGroup $group, SettingsRequest $request, UpdateSettingsGroup $updateSettings): RedirectResponse
    {
        try {
            $updateSettings->handle($request->toInput());
        } catch (ValidationFailed $failure) {
            throw (new ValidationFailedTranslator)->toValidationException($failure);
        }

        return redirect()->route('admin.settings.edit', ['tab' => $group->value])->with('success', 'Settings saved.');
    }
}
