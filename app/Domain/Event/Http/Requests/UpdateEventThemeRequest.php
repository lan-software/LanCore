<?php

namespace App\Domain\Event\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @see docs/mil-std-498/SSS.md CAP-EVT-008
 * @see docs/mil-std-498/SRS.md THM-F-004
 */
class UpdateEventThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'theme_id' => ['nullable', 'integer', 'exists:themes,id'],
        ];
    }
}
