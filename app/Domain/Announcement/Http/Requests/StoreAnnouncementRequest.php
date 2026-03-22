<?php

namespace App\Domain\Announcement\Http\Requests;

use App\Domain\Announcement\Enums\AnnouncementPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
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
            'description' => ['nullable', 'string'],
            'priority' => ['required', Rule::enum(AnnouncementPriority::class)],
            'event_id' => ['required', 'exists:events,id'],
            'publish_now' => ['sometimes', 'boolean'],
        ];
    }
}
