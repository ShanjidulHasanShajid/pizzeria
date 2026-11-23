<?php

declare(strict_types=1);

namespace App\Modules\Content\Presentation\Http\Requests;

use App\Modules\Content\Application\DTOs\RenameNavItemInput;
use App\Modules\Content\Domain\Enums\NavRoute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class RenameNavItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-content');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return ['label' => ['nullable', 'string', 'max:255']];
    }

    public function toInput(NavRoute $route): RenameNavItemInput
    {
        return new RenameNavItemInput($route, $this->string('label')->toString());
    }
}
