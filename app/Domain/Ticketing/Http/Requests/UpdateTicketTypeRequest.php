<?php

namespace App\Domain\Ticketing\Http\Requests;

use App\Domain\Ticketing\Enums\CheckInMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketTypeRequest extends FormRequest
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
        $ticketType = $this->route('ticketType');
        $isLocked = $ticketType && $ticketType->is_locked;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'max_per_user' => ['nullable', 'integer', 'min:1'],
            'is_hidden' => ['sometimes', 'boolean'],
            'purchase_from' => ['nullable', 'date'],
            'purchase_until' => ['nullable', 'date', 'after_or_equal:purchase_from'],
            'ticket_category_id' => ['nullable', 'string', 'ulid', 'exists:ticket_categories,id'],
            'ticket_group_id' => ['nullable', 'string', 'ulid', 'exists:ticket_groups,id'],
            'notify_on_release' => ['sometimes', 'boolean'],
            'notify_on_end' => ['sometimes', 'boolean'],
            'notify_on_end_lead_minutes' => ['required_if:notify_on_end,true', 'integer', 'min:1', 'max:43200'],
        ];

        if (! $isLocked) {
            $rules['price'] = ['required', 'integer', 'min:0'];
            $rules['quota'] = ['required', 'integer', 'min:1'];
            $rules['seats_per_user'] = ['required', 'integer', 'min:1'];
            $rules['max_users_per_ticket'] = ['sometimes', 'integer', 'min:1'];
            $rules['check_in_mode'] = ['sometimes', Rule::enum(CheckInMode::class)];
            $rules['is_seatable'] = ['sometimes', 'boolean'];
            $rules['event_id'] = ['required', 'string', 'ulid', 'exists:events,id'];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_seatable' => $this->boolean('is_seatable'),
            'is_hidden' => $this->boolean('is_hidden'),
            'notify_on_release' => $this->boolean('notify_on_release'),
            'notify_on_end' => $this->boolean('notify_on_end'),
        ]);
    }
}
