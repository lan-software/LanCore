<?php

namespace App\Domain\News\Http\Requests;

use App\Domain\News\Enums\ArticleVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNewsArticleRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:50'],
            'image' => ['nullable', 'image', 'max:5120'],
            'remove_image' => ['sometimes', 'boolean'],
            'visibility' => ['required', 'string', Rule::enum(ArticleVisibility::class)],
            'is_archived' => ['sometimes', 'boolean'],
            'comments_enabled' => ['sometimes', 'boolean'],
            'comments_require_approval' => ['sometimes', 'boolean'],
            'notify_users' => ['sometimes', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'og_title' => ['nullable', 'string', 'max:70'],
            'og_description' => ['nullable', 'string', 'max:200'],
            'og_image' => ['nullable', 'image', 'max:5120'],
            'remove_og_image' => ['sometimes', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
