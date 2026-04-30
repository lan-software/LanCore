<?php

namespace App\Domain\DataLifecycle\Http\Requests;

use App\Domain\DataLifecycle\Enums\Permission;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminRequestDeletionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::RequestUserDeletion) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::exists((new User)->getTable(), 'id')->whereNull('deleted_at')],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }
}
