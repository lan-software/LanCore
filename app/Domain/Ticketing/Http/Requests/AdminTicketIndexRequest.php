<?php

namespace App\Domain\Ticketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminTicketIndexRequest extends FormRequest
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
            'sort' => ['nullable', 'string', 'in:id,status,created_at,checked_in_at'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
            'status' => ['nullable', 'string', 'in:active,checked_in,cancelled'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
        ];
    }
}
