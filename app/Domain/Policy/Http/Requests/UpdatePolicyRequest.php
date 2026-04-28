<?php

namespace App\Domain\Policy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $policyId = $this->route('policy')?->id;

        return [
            'policy_type_id' => ['sometimes', 'integer', 'exists:policy_types,id'],
            'key' => ['sometimes', 'string', 'max:64', 'regex:/^[a-z0-9_-]+$/', Rule::unique('policies', 'key')->ignore($policyId)],
            'name' => ['sometimes', 'string', 'max:128'],
            'description' => ['nullable', 'string'],
            'is_required_for_registration' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
