<?php

namespace App\Domain\Shop\Http\Requests;

use App\Domain\Shop\Enums\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateShopCurrencyRequest extends FormRequest
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
            'currency' => ['required', 'string', new Enum(Currency::class), Rule::in(array_map(
                fn (Currency $c): string => $c->value,
                Currency::cases(),
            ))],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'currency' => strtolower((string) $this->input('currency', '')),
        ]);
    }
}
