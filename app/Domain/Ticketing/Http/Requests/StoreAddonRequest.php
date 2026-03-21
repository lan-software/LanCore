<?php

namespace App\Domain\Ticketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddonRequest extends FormRequest
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
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'quota' => ['nullable', 'integer', 'min:1'],
            'seats_consumed' => ['required', 'integer', 'min:0'],
            'requires_ticket' => ['sometimes', 'boolean'],
            'is_hidden' => ['sometimes', 'boolean'],
            'event_id' => ['required', 'integer', 'exists:events,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'requires_ticket' => $this->boolean('requires_ticket'),
            'is_hidden' => $this->boolean('is_hidden'),
        ]);
    }
}
