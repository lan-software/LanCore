<?php

namespace App\Domain\DataLifecycle\Http\Requests;

use App\Domain\DataLifecycle\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class AdminAnonymizeImmediatelyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::RequestUserDeletion) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }
}
