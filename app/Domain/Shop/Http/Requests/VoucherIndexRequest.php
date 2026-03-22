<?php

namespace App\Domain\Shop\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VoucherIndexRequest extends FormRequest
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
            'sort' => ['nullable', 'string', 'in:code,type,discount_percent,discount_amount,max_uses,times_used,is_active,created_at'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
        ];
    }
}
