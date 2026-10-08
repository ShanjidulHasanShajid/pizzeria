<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Requests;

use App\Modules\Identity\Domain\Enums\Ability;
use App\Modules\Identity\Presentation\Http\Requests\Concerns\NormalizesContactFields;
use App\Modules\Shared\Presentation\Rules\BangladeshPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class UpdateUserRequest extends FormRequest
{
    use NormalizesContactFields;

    public function authorize(): bool
    {
        return Gate::allows(Ability::ManageAdminUsers->value);
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeContactFields();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $userId = (int) $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:254', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', new BangladeshPhone, Rule::unique('users', 'phone')->ignore($userId)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }
}
