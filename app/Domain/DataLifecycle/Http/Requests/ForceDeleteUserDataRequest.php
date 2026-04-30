<?php

namespace App\Domain\DataLifecycle\Http\Requests;

use App\Domain\DataLifecycle\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class ForceDeleteUserDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::ForceDeleteUserData) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'confirmation' => ['required', 'string', 'in:I UNDERSTAND THIS IS IRREVERSIBLE'],
        ];
    }
}
