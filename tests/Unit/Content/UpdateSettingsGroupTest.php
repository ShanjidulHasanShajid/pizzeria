<?php

declare(strict_types=1);

use App\Modules\Content\Application\DTOs\ImageUpload;
use App\Modules\Content\Application\DTOs\UpdateSettingsGroupInput;
use App\Modules\Content\Application\Services\SettingImages;
use App\Modules\Content\Application\UseCases\Settings\UpdateSettingsGroup;
use App\Modules\Content\Domain\Enums\SettingGroup;
use App\Modules\Content\Domain\Services\SettingValues;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use Tests\Unit\Catalog\Fakes\InMemoryFileStorage;
use Tests\Unit\Catalog\Fakes\RecordingImageProcessor;
use Tests\Unit\Content\Fakes\InMemorySettingsRepository;

beforeEach(function (): void {
    $this->settings = new InMemorySettingsRepository(['phone' => '16247']);
    $this->files = new InMemoryFileStorage;
    $useCase = new UpdateSettingsGroup($this->settings, new SettingValues, new SettingImages($this->files, new RecordingImageProcessor));
    $this->update = fn (SettingGroup $group, array $values = [], array $uploads = [], array $removed = []) => $useCase
        ->handle(new UpdateSettingsGroupInput($group, $values, $uploads, $removed));
});

it('saves the checked values of one group and leaves the others alone', function (): void {
    ($this->update)(SettingGroup::Commerce, ['vat_percentage' => '5', 'store_open' => '0', 'phone' => '999']);

    expect($this->settings->all())->toBe(['phone' => '16247', 'vat_percentage' => '5.00', 'store_open' => '0']);
});

it('reports every wrong field at once and saves nothing', function (): void {
    try {
        ($this->update)(SettingGroup::Contact, ['phone' => 'call me', 'email' => 'info@', 'address_line' => 'Banani']);
    } catch (ValidationFailed $failure) {
        expect(array_keys($failure->errors()))->toBe(['phone', 'email'])
            ->and($this->settings->all())->toBe(['phone' => '16247']);

        return;
    }

    throw new RuntimeException('Wrong values must be refused.');
});

it('stores the logo in its shape and the browser icon as a square', function (): void {
    ($this->update)(SettingGroup::General, ['site_name' => 'Dhaka Pizza'], ['logo' => new ImageUpload('L'), 'favicon' => new ImageUpload('F')]);

    $saved = $this->settings->all();

    expect($this->files->contents($saved['logo']))->toBe('webp(resize-600x240(L))')
        ->and($this->files->contents($saved['favicon']))->toBe('webp(thumbnail-128(F))');
});

it('replaces and removes an image together with its old file', function (): void {
    ($this->update)(SettingGroup::General, [], ['logo' => new ImageUpload('old')]);
    ($this->update)(SettingGroup::General, [], ['logo' => new ImageUpload('new')]);

    expect($this->files->paths())->toBe([$this->settings->all()['logo']]);

    ($this->update)(SettingGroup::General, [], [], ['logo']);

    expect($this->settings->all()['logo'])->toBe('')
        ->and($this->files->paths())->toBe([]);
});

it('deletes the new file again when saving fails, and keeps the old one', function (): void {
    ($this->update)(SettingGroup::General, [], ['logo' => new ImageUpload('old')]);
    $old = $this->settings->all()['logo'];
    $this->settings->failNextSave();

    expect(fn () => ($this->update)(SettingGroup::General, [], ['logo' => new ImageUpload('new')]))->toThrow(RuntimeException::class);
    expect($this->files->paths())->toBe([$old])
        ->and($this->settings->all()['logo'])->toBe($old);
});

it('stores no file when another field of the form is wrong', function (): void {
    expect(fn () => ($this->update)(SettingGroup::General, ['site_name' => ''], ['logo' => new ImageUpload('L')]))->toThrow(ValidationFailed::class);

    expect($this->files->paths())->toBe([]);
});
