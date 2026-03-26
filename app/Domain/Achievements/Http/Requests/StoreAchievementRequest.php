<?php

namespace App\Domain\Achievements\Http\Requests;

use App\Domain\Achievements\Enums\GrantableEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAchievementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'event_classes' => $this->input('event_classes', []),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'notification_text' => ['nullable', 'string', 'max:500'],
            'color' => ['required', 'string', 'max:7', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icon' => ['required', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
            'event_classes' => ['sometimes', 'array'],
            'event_classes.*' => ['string', Rule::in(array_column(GrantableEvent::cases(), 'value'))],
        ];
    }
}
