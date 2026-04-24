<?php

namespace App\Domain\Seating\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @see docs/mil-std-498/IDD.md §3.17 Seat Plan Background Upload
 */
class StoreSeatPlanBackgroundRequest extends FormRequest
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
            'image' => [
                'required',
                'file',
                'image',
                'mimes:png,jpg,jpeg,webp',
                'max:5120',
            ],
        ];
    }
}
