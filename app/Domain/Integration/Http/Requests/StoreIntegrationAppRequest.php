<?php

namespace App\Domain\Integration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIntegrationAppRequest extends FormRequest
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
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('integration_apps', 'slug')],
            'description' => ['nullable', 'string', 'max:1000'],
            'callback_url' => ['nullable', 'url', 'max:2048'],
            'nav_url' => ['nullable', 'url', 'max:2048'],
            'nav_icon' => ['nullable', 'string', 'max:100'],
            'nav_label' => ['nullable', 'string', 'max:100'],
            'allowed_scopes' => ['sometimes', 'array'],
            'allowed_scopes.*' => ['string', Rule::in(['user:read', 'user:email', 'user:roles'])],
            'is_active' => ['sometimes', 'boolean'],
            'send_announcements' => ['sometimes', 'boolean'],
            'announcement_endpoint' => ['nullable', 'required_if:send_announcements,true', 'url', 'max:2048'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'send_announcements' => $this->boolean('send_announcements'),
            'allowed_scopes' => $this->input('allowed_scopes', []),
        ]);
    }
}
