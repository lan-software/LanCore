<?php

namespace App\Domain\Integration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIntegrationTokenRequest extends FormRequest
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
            'expires_at' => ['nullable', 'date', 'after:today'],
        ];
    }
}
