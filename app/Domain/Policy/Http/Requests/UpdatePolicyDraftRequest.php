<?php

namespace App\Domain\Policy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePolicyDraftRequest extends FormRequest
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
            'content' => ['present', 'nullable', 'string'],
        ];
    }
}
