<?php

namespace App\Domain\DataLifecycle\Http\Requests;

use App\Domain\DataLifecycle\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRetentionPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::ManageRetentionPolicies) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'retention_days' => ['required', 'integer', 'min:0', 'max:36500'],
            'legal_basis' => ['required', 'string', 'min:5', 'max:2000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'can_be_force_deleted' => ['required', 'boolean'],
        ];
    }
}
