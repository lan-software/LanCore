<?php

namespace App\Domain\Competition\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompetitionIndexRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'in:name,status,type,created_at,starts_at'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'status' => ['nullable', 'string'],
        ];
    }
}
