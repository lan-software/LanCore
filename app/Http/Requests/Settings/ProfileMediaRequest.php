<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @see docs/mil-std-498/SRS.md USR-F-024
 * @see docs/mil-std-498/SSS.md SEC-022
 */
class ProfileMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'image' => [
                'required',
                'file',
                'image',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:5120',
            ],
        ];
    }
}
