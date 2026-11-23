<?php

declare(strict_types=1);

namespace App\Modules\Customization\Presentation\Livewire\Admin;

use App\Modules\Customization\Application\DTOs\AddOptionInput;
use App\Modules\Customization\Application\DTOs\AdminOptionRow;
use App\Modules\Customization\Application\DTOs\ChangeOptionImageInput;
use App\Modules\Customization\Application\DTOs\ImageUpload;
use App\Modules\Customization\Application\DTOs\OptionItemInput;
use App\Modules\Customization\Application\DTOs\ReorderOptionsInput;
use App\Modules\Customization\Application\DTOs\UpdateOptionInput;
use App\Modules\Customization\Application\Queries\AdminOptionQuery;
use App\Modules\Customization\Application\UseCases\Options\AddOption;
use App\Modules\Customization\Application\UseCases\Options\ChangeOptionImage;
use App\Modules\Customization\Application\UseCases\Options\RemoveOption;
use App\Modules\Customization\Application\UseCases\Options\ReorderOptions;
use App\Modules\Customization\Application\UseCases\Options\ToggleOptionAvailable;
use App\Modules\Customization\Application\UseCases\Options\UpdateOption;
use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Enums\PriceMode;
use App\Modules\Shared\Application\Ports\FileStorage;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * The options of one group as editable rows (8.29): name, image, veg, price mode, flat price,
 * sold out, order, delete. Every action calls one use case and saves at once.
 * Used as <livewire:customization.admin.option-manager :option-group-id="..." />.
 */
final class OptionManager extends Component
{
    use WithFileUploads;

    private const EMPTY_ROW = ['name' => '', 'isVeg' => false, 'priceMode' => 'flat', 'flatPrice' => ''];

    /** Locked: the browser cannot change which group this component works on. */
    #[Locked]
    public int $optionGroupId;

    /** @var array<int, array{name: string, isVeg: bool, priceMode: string, flatPrice: string}> option id => form fields */
    public array $rows = [];

    /** @var array{name: string, isVeg: bool, priceMode: string, flatPrice: string} the "add" form */
    public array $new = self::EMPTY_ROW;

    /** @var array<int, TemporaryUploadedFile> option id => chosen image, not saved yet */
    public array $uploads = [];

    public string $status = '';

    private AdminOptionQuery $query;

    private FileStorage $files;

    /**
     * Runs on EVERY request of this component: Livewire actions arrive at /livewire/update,
     * so the route middleware does not protect them. The gate is checked here again.
     */
    public function boot(AdminOptionQuery $query, FileStorage $files): void
    {
        abort_unless(Gate::allows('manage-catalog'), 403);

        $this->query = $query;
        $this->files = $files;
    }

    public function mount(int $optionGroupId): void
    {
        $this->optionGroupId = $optionGroupId;

        foreach ($this->options() as $option) {
            $this->rows[$option->id] = $this->toRow($option);
        }
    }

    public function add(AddOption $addOption): void
    {
        $this->validateRow('new');

        $this->run('new', fn () => $addOption->handle(new AddOptionInput(
            $this->optionGroupId,
            $this->new['name'],
            (bool) $this->new['isVeg'],
            PriceMode::from($this->new['priceMode']),
            $this->new['flatPrice'],
        )));

        $this->reset('new');
        $this->addMissingRows();
        $this->status = 'Option added.';
    }

    public function saveRow(UpdateOption $updateOption, int $optionId): void
    {
        $this->validateRow("rows.{$optionId}");
        $row = $this->rows[$optionId];

        $this->run("rows.{$optionId}", fn () => $updateOption->handle(new UpdateOptionInput(
            $this->optionGroupId,
            $optionId,
            $row['name'],
            (bool) $row['isVeg'],
            PriceMode::from($row['priceMode']),
            $row['flatPrice'],
        )));

        $this->status = "\"{$row['name']}\" saved.";
    }

    public function toggle(ToggleOptionAvailable $toggleAvailable, int $optionId): void
    {
        $isAvailable = $this->run('', fn (): bool => $toggleAvailable->handle(new OptionItemInput($this->optionGroupId, $optionId)));
        $this->status = $isAvailable ? 'The option is available again.' : 'The option is sold out now.';
    }

    public function remove(RemoveOption $removeOption, int $optionId): void
    {
        $this->run('', fn () => $removeOption->handle(new OptionItemInput($this->optionGroupId, $optionId)));
        unset($this->rows[$optionId], $this->uploads[$optionId]);
        $this->status = 'Option moved to the trash.';
    }

    /**
     * @param  array<int, int|string>  $ids  sent by the drag-and-drop list
     */
    public function reorder(ReorderOptions $reorderOptions, array $ids): void
    {
        $optionIds = array_values(array_map(fn (int|string $id): int => (int) $id, $ids));

        $this->run('', fn () => $reorderOptions->handle(new ReorderOptionsInput($this->optionGroupId, $optionIds)));
        $this->status = 'New order saved.';
    }

    public function saveImage(ChangeOptionImage $changeImage, int $optionId): void
    {
        $this->validate([
            "uploads.{$optionId}" => [
                'required',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max(2 * 1024)
                    ->dimensions(Rule::dimensions()->minWidth(400)->minHeight(400)),
            ],
        ], [
            "uploads.{$optionId}.required" => 'Choose an image first.',
            "uploads.{$optionId}.dimensions" => 'The image must be at least 400 x 400 pixels.',
        ], ["uploads.{$optionId}" => 'image']);

        $image = new ImageUpload((string) $this->uploads[$optionId]->get());

        $this->run('', fn () => $changeImage->handle(new ChangeOptionImageInput($this->optionGroupId, $optionId, $image)));
        unset($this->uploads[$optionId]);
        $this->status = 'Image saved.';
    }

    public function removeImage(ChangeOptionImage $changeImage, int $optionId): void
    {
        $this->run('', fn () => $changeImage->handle(new ChangeOptionImageInput($this->optionGroupId, $optionId, null)));
        $this->status = 'Image removed.';
    }

    public function render(): View
    {
        return view('customization::livewire.option-manager', [
            'options' => $this->options(),
            'priceModes' => PriceMode::cases(),
            'imageUrl' => fn (string $path): string => $this->files->url($path),
        ]);
    }

    /**
     * @return list<AdminOptionRow>
     */
    private function options(): array
    {
        return $this->query->group($this->optionGroupId)->options ?? [];
    }

    /** New options get a row; rows with unsaved typing are left alone. */
    private function addMissingRows(): void
    {
        foreach ($this->options() as $option) {
            $this->rows[$option->id] ??= $this->toRow($option);
        }
    }

    /**
     * @return array{name: string, isVeg: bool, priceMode: string, flatPrice: string}
     */
    private function toRow(AdminOptionRow $option): array
    {
        return [
            'name' => $option->name,
            'isVeg' => $option->isVeg,
            'priceMode' => $option->isPricedPerSize ? PriceMode::PerSize->value : PriceMode::Flat->value,
            'flatPrice' => $option->flatPriceTaka ?? '',
        ];
    }

    /**
     * Checks the fields of one row ("new" or "rows.5"), not of all rows.
     */
    private function validateRow(string $prefix): void
    {
        $this->validate([
            "{$prefix}.name" => ['required', 'string', 'max:'.Option::NAME_MAX_LENGTH],
            "{$prefix}.isVeg" => ['boolean'],
            "{$prefix}.priceMode" => ['required', Rule::enum(PriceMode::class)],
            "{$prefix}.flatPrice" => ["required_if:{$prefix}.priceMode,flat", 'nullable', 'string', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
        ], [
            "{$prefix}.name.required" => 'Give the option a name.',
            "{$prefix}.flatPrice.required_if" => 'Give the option a price. 0 means free.',
            "{$prefix}.flatPrice.regex" => 'Enter the price in taka, for example 60 or 60.50.',
        ], ["{$prefix}.name" => 'name', "{$prefix}.priceMode" => 'price mode']);
    }

    /**
     * Runs a use case. Its form errors ("name", "flat_price") go under the fields of this row.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $action
     * @return TResult
     */
    private function run(string $prefix, Closure $action): mixed
    {
        try {
            return $action();
        } catch (ValidationFailed $failure) {
            $errors = [];

            foreach ($failure->errors() as $field => $messages) {
                $errors[match ($field) {
                    'name' => "{$prefix}.name",
                    'flat_price' => "{$prefix}.flatPrice",
                    default => $field,
                }] = $messages;
            }

            throw ValidationException::withMessages($errors);
        } catch (EntityNotFound) {
            abort(404);
        }
    }
}
