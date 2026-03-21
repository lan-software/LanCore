<?php

namespace App\Domain\Games\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGameModeRequest extends FormRequest
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
            'slug' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'team_size' => ['required', 'integer', 'min:1'],
            'parameters' => ['nullable', 'json'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
