<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use App\Domain\Profile\Enums\AvatarSource;
use App\Domain\Profile\Enums\ProfileVisibility;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules($this->user()->id),
            'short_bio' => ['nullable', 'string', 'max:160'],
            'profile_description' => ['nullable', 'string', 'max:5000'],
            'profile_emoji' => ['nullable', 'string', 'max:16', 'regex:/^[\p{Extended_Pictographic}\x{200D}\x{FE0F}\x{E0020}-\x{E007F}]+$/u'],
            'avatar_source' => ['nullable', new Enum(AvatarSource::class)],
            'profile_visibility' => ['nullable', new Enum(ProfileVisibility::class)],
        ];
    }
}
