<?php

namespace App\Domain\Shop\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequirementRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'requirements_content' => ['nullable', 'string'],
            'acknowledgements' => ['nullable', 'array'],
            'acknowledgements.*' => ['required', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
            'requires_scroll' => ['sometimes', 'boolean'],
            'ticket_type_ids' => ['nullable', 'array'],
            'ticket_type_ids.*' => ['integer', 'exists:ticket_types,id'],
            'addon_ids' => ['nullable', 'array'],
            'addon_ids.*' => ['integer', 'exists:ticket_addons,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'requires_scroll' => $this->boolean('requires_scroll'),
        ]);
    }
}
