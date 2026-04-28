<?php

namespace App\Domain\Policy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePolicyTypeRequest extends FormRequest
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
        $typeId = $this->route('policyType')?->id;

        return [
            'key' => ['sometimes', 'string', 'max:64', 'regex:/^[a-z0-9_-]+$/', Rule::unique('policy_types', 'key')->ignore($typeId)],
            'label' => ['sometimes', 'string', 'max:128'],
            'description' => ['nullable', 'string'],
        ];
    }
}
