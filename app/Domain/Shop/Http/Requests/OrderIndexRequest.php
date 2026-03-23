<?php

namespace App\Domain\Shop\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderIndexRequest extends FormRequest
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
            'sort' => ['nullable', 'string', 'in:id,status,payment_method,total,created_at'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
            'status' => ['nullable', 'string', 'in:pending,completed,failed,refunded'],
            'payment_method' => ['nullable', 'string', 'in:stripe,on_site'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
        ];
    }
}
