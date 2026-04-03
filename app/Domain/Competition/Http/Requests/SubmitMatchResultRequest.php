<?php

namespace App\Domain\Competition\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitMatchResultRequest extends FormRequest
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
            'lanbrackets_match_id' => ['required', 'integer'],
            'scores' => ['required', 'array', 'min:2'],
            'scores.*.participant_id' => ['required', 'integer'],
            'scores.*.score' => ['required', 'integer', 'min:0'],
            'screenshot' => ['required', 'file', 'image', 'max:5120'],
        ];
    }
}
