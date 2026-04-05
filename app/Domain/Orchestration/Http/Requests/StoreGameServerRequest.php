<?php

namespace App\Domain\Orchestration\Http\Requests;

use App\Domain\Orchestration\Enums\GameServerAllocationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGameServerRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'game_id' => ['required', 'exists:games,id'],
            'game_mode_id' => ['nullable', 'exists:game_modes,id'],
            'allocation_type' => ['required', Rule::enum(GameServerAllocationType::class)],
            'credentials' => ['nullable', 'array'],
            'credentials.rcon_password' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
