<?php

namespace App\Domain\News\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NewsArticleIndexRequest extends FormRequest
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
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort' => ['sometimes', 'nullable', 'string', Rule::in(['title', 'visibility', 'published_at', 'created_at'])],
            'direction' => ['sometimes', 'nullable', 'string', Rule::in(['asc', 'desc'])],
            'visibility' => ['sometimes', 'nullable', 'string', Rule::in(['draft', 'internal', 'public'])],
            'per_page' => ['sometimes', 'nullable', 'integer', Rule::in([10, 20, 50, 100])],
        ];
    }
}
