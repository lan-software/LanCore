<?php

namespace App\Domain\Ticketing\Http\Requests;

use App\Domain\Ticketing\Enums\CheckInMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketTypeRequest extends FormRequest
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
            'quota' => ['required', 'integer', 'min:1'],
            'max_per_user' => ['nullable', 'integer', 'min:1'],
            'seats_per_ticket' => ['required', 'integer', 'min:1'],
            'max_users_per_ticket' => ['sometimes', 'integer', 'min:1'],
            'check_in_mode' => ['sometimes', Rule::enum(CheckInMode::class)],
            'is_seatable' => ['sometimes', 'boolean'],
            'is_hidden' => ['sometimes', 'boolean'],
            'purchase_from' => ['nullable', 'date'],
            'purchase_until' => ['nullable', 'date', 'after_or_equal:purchase_from'],
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'ticket_category_id' => ['nullable', 'integer', 'exists:ticket_categories,id'],
            'ticket_group_id' => ['nullable', 'integer', 'exists:ticket_groups,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_seatable' => $this->boolean('is_seatable'),
            'is_hidden' => $this->boolean('is_hidden'),
        ]);
    }
}
