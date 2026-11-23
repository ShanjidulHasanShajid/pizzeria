<?php

declare(strict_types=1);

namespace App\Modules\Customization\Presentation\Livewire\Admin;

use App\Modules\Customization\Application\DTOs\AttachedGroupRow;
use App\Modules\Customization\Application\DTOs\AttachOptionGroupInput;
use App\Modules\Customization\Application\DTOs\DetachOptionGroupInput;
use App\Modules\Customization\Application\DTOs\ReorderAttachedGroupsInput;
use App\Modules\Customization\Application\DTOs\SelectionRulesInput;
use App\Modules\Customization\Application\DTOs\UpdateAttachedRulesInput;
use App\Modules\Customization\Application\Queries\AdminOptionQuery;
use App\Modules\Customization\Application\UseCases\Attachments\AttachOptionGroup;
use App\Modules\Customization\Application\UseCases\Attachments\DetachOptionGroup;
use App\Modules\Customization\Application\UseCases\Attachments\ReorderAttachedGroups;
use App\Modules\Customization\Application\UseCases\Attachments\UpdateAttachedRules;
use App\Modules\Customization\Domain\ValueObjects\SelectionRules;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The Options section of the product form (8.31): attach groups, set their rules for this product,
 * reorder and detach. Every action calls one use case and saves at once.
 * Used as <livewire:customization.admin.product-options :product-id="..." />.
 */
final class ProductOptions extends Component
{
    private const NEW_ROW = ['optionGroupId' => '', 'isRequired' => false, 'minimum' => 0, 'maximum' => 1, 'freeCount' => 0];

    /** Locked: the browser cannot change which product this component works on. */
    #[Locked]
    public int $productId;

    /** @var array<int, array{isRequired: bool, minimum: int|string, maximum: int|string, freeCount: int|string}> attachment id => rules */
    public array $rows = [];

    /** @var array{optionGroupId: int|string, isRequired: bool, minimum: int|string, maximum: int|string, freeCount: int|string} */
    public array $new = self::NEW_ROW;

    public string $status = '';

    private AdminOptionQuery $query;

    /** Runs on every request, so also on /livewire/update: the gate is checked here again. */
    public function boot(AdminOptionQuery $query): void
    {
        abort_unless(Gate::allows('manage-catalog'), 403);

        $this->query = $query;
    }

    public function mount(int $productId): void
    {
        $this->productId = $productId;
        $this->addMissingRows();
    }

    public function attach(AttachOptionGroup $attachGroup): void
    {
        $this->validate(
            ['new.optionGroupId' => ['required', 'integer', 'min:1'], ...$this->ruleFields('new')],
            ['new.optionGroupId.required' => 'Choose a group first.'],
            $this->fieldNames('new'),
        );

        $this->run('new', fn () => $attachGroup->handle(new AttachOptionGroupInput(
            $this->productId,
            (int) $this->new['optionGroupId'],
            $this->rulesInput($this->new),
        )));

        $this->reset('new');
        $this->addMissingRows();
        $this->status = 'Option group attached.';
    }

    public function saveRules(UpdateAttachedRules $updateRules, int $attachmentId): void
    {
        $this->validate($this->ruleFields("rows.{$attachmentId}"), [], $this->fieldNames("rows.{$attachmentId}"));

        $this->run("rows.{$attachmentId}", fn () => $updateRules->handle(new UpdateAttachedRulesInput(
            $this->productId,
            $attachmentId,
            $this->rulesInput($this->rows[$attachmentId]),
        )));

        $this->status = 'Rules saved.';
    }

    public function detach(DetachOptionGroup $detachGroup, int $attachmentId): void
    {
        $this->run('', fn () => $detachGroup->handle(new DetachOptionGroupInput($this->productId, $attachmentId)));
        unset($this->rows[$attachmentId]);
        $this->status = 'Option group detached.';
    }

    /**
     * @param  array<int, int|string>  $ids  sent by the drag-and-drop list
     */
    public function reorder(ReorderAttachedGroups $reorderGroups, array $ids): void
    {
        $attachmentIds = array_values(array_map(fn (int|string $id): int => (int) $id, $ids));

        $this->run('', fn () => $reorderGroups->handle(new ReorderAttachedGroupsInput($this->productId, $attachmentIds)));
        $this->status = 'New order saved.';
    }

    public function render(): View
    {
        $attached = $this->query->attachedGroups($this->productId);
        $attachedIds = array_map(fn (AttachedGroupRow $row): int => $row->optionGroupId, $attached);
        $choices = [];

        foreach ($this->query->groupTable() as $group) {
            if (! in_array($group->id, $attachedIds, true)) {
                $choices[$group->id] = $group->isActive ? $group->name : "{$group->name} (inactive)";
            }
        }

        return view('customization::livewire.product-options', [
            'attached' => $attached,
            'choices' => $choices,
        ]);
    }

    /** New attachments get a row; rows with unsaved typing are left alone. */
    private function addMissingRows(): void
    {
        foreach ($this->query->attachedGroups($this->productId) as $row) {
            $this->rows[$row->attachmentId] ??= [
                'isRequired' => $row->isRequired,
                'minimum' => $row->minimum,
                'maximum' => $row->maximum,
                'freeCount' => $row->freeCount,
            ];
        }
    }

    /**
     * @return array<string, list<string>>
     */
    private function ruleFields(string $prefix): array
    {
        return [
            "{$prefix}.isRequired" => ['boolean'],
            "{$prefix}.minimum" => ['required', 'integer', 'between:0,'.SelectionRules::MAX_PICKS],
            "{$prefix}.maximum" => ['required', 'integer', 'between:1,'.SelectionRules::MAX_PICKS],
            "{$prefix}.freeCount" => ['required', 'integer', 'between:0,'.SelectionRules::MAX_PICKS],
        ];
    }

    /**
     * @return array<string, string> readable names for the messages
     */
    private function fieldNames(string $prefix): array
    {
        return ["{$prefix}.minimum" => 'minimum', "{$prefix}.maximum" => 'maximum', "{$prefix}.freeCount" => 'free picks'];
    }

    /**
     * @param  array{isRequired: bool, minimum: int|string, maximum: int|string, freeCount: int|string, ...}  $row
     */
    private function rulesInput(array $row): SelectionRulesInput
    {
        return new SelectionRulesInput((bool) $row['isRequired'], (int) $row['minimum'], (int) $row['maximum'], (int) $row['freeCount']);
    }

    /**
     * Runs a use case. Its form errors go under the fields of this row.
     */
    private function run(string $prefix, Closure $action): void
    {
        try {
            $action();
        } catch (ValidationFailed $failure) {
            $errors = [];

            foreach ($failure->errors() as $field => $messages) {
                $errors[match ($field) {
                    'rules' => "{$prefix}.rules",
                    'option_group_id' => "{$prefix}.optionGroupId",
                    default => $field,
                }] = $messages;
            }

            throw ValidationException::withMessages($errors);
        } catch (EntityNotFound) {
            abort(404);
        }
    }
}
