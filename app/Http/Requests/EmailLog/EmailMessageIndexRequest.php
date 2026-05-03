<?php

namespace App\Http\Requests\EmailLog;

use App\Domain\EmailLog\Enums\EmailMessageStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmailMessageIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', 'string', Rule::in(array_column(EmailMessageStatus::cases(), 'value'))],
            'source' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort' => ['sometimes', 'nullable', 'string', Rule::in(['created_at', 'subject', 'status'])],
            'direction' => ['sometimes', 'nullable', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'nullable', 'integer', Rule::in([10, 20, 50, 100])],
        ];
    }
}
