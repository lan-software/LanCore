<?php

namespace App\Domain\Notification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePushSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'endpoint' => ['required', 'string', 'url'],
            'public_key' => ['required', 'string'],
            'auth_token' => ['required', 'string'],
            'content_encoding' => ['nullable', 'string', 'in:aesgcm,aes128gcm'],
        ];
    }
}
