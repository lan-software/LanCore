<?php

namespace App\Domain\News\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NewsCommentIndexRequest extends FormRequest
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
            'sort' => ['sometimes', 'nullable', 'string', Rule::in(['created_at', 'is_approved'])],
            'direction' => ['sometimes', 'nullable', 'string', Rule::in(['asc', 'desc'])],
            'article_id' => ['sometimes', 'nullable', 'integer', 'exists:news_articles,id'],
            'is_approved' => ['sometimes', 'nullable', 'string', Rule::in(['0', '1'])],
            'visibility' => ['sometimes', 'nullable', 'string', Rule::in(['draft', 'internal', 'public'])],
            'tag' => ['sometimes', 'nullable', 'string', 'max:50'],
            'per_page' => ['sometimes', 'nullable', 'integer', Rule::in([10, 20, 50, 100])],
        ];
    }
}
