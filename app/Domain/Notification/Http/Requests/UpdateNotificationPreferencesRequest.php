<?php

namespace App\Domain\Notification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
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
            'mail_on_news' => ['required', 'boolean'],
            'mail_on_events' => ['required', 'boolean'],
            'mail_on_news_comments' => ['required', 'boolean'],
            'mail_on_program_time_slots' => ['required', 'boolean'],
            'push_on_news' => ['required', 'boolean'],
            'push_on_events' => ['required', 'boolean'],
            'push_on_news_comments' => ['required', 'boolean'],
            'push_on_program_time_slots' => ['required', 'boolean'],
        ];
    }
}
