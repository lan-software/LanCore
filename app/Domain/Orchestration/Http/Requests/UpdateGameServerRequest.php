<?php

namespace App\Domain\Orchestration\Http\Requests;

use App\Domain\Orchestration\Enums\GameServerAllocationType;
use App\Domain\Orchestration\Enums\GameServerStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGameServerRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'host' => ['sometimes', 'string', 'max:255'],
            'port' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'game_id' => ['sometimes', 'exists:games,id'],
            'game_mode_id' => ['nullable', 'exists:game_modes,id'],
            'allocation_type' => ['sometimes', Rule::enum(GameServerAllocationType::class)],
            'status' => ['sometimes', Rule::enum(GameServerStatus::class)],
            'credentials' => ['nullable', 'array'],
            'credentials.rcon_password' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
