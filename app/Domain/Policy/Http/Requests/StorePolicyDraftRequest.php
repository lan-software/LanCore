<?php

namespace App\Domain\Policy\Http\Requests;

use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePolicyDraftRequest extends FormRequest
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
            'locale' => [
                'required',
                'string',
                Rule::in(SetLocale::AVAILABLE),
                Rule::unique('policy_locale_drafts', 'locale')
                    ->where('policy_id', $this->route('policy')->id),
            ],
        ];
    }
}
