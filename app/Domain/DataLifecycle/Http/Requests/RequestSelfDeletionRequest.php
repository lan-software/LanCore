<?php

namespace App\Domain\DataLifecycle\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestSelfDeletionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->user()->isPendingDeletion();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'current_password'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
