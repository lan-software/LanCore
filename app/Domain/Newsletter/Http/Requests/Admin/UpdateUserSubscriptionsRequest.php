<?php

namespace App\Domain\Newsletter\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @see docs/mil-std-498/SRS.md NLT-F-003
 */
class UpdateUserSubscriptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'subscribed_list_ids' => ['present', 'array'],
            'subscribed_list_ids.*' => ['string', 'ulid', Rule::exists('newsletter_lists', 'id')->where('is_user_selectable', true)],
        ];
    }
}
