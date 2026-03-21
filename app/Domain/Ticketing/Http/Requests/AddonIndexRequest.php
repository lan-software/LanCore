<?php

namespace App\Domain\Ticketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddonIndexRequest extends FormRequest
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
            'sort' => ['nullable', 'string', 'in:name,price,quota,seats_consumed,created_at'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
        ];
    }
}
