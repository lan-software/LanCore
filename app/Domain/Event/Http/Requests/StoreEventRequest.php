<?php

namespace App\Domain\Event\Http\Requests;

use App\Domain\Event\Enums\AttendanceMode;
use App\Domain\Event\Enums\EventSyndicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'banner_images' => ['nullable', 'array', 'max:10'],
            'banner_images.*' => ['image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
            'seat_capacity' => ['nullable', 'integer', 'min:1'],
            'venue_id' => ['nullable', 'string', 'ulid', 'exists:venues,id'],
            ...$this->lppsRules(),
        ];
    }

    /**
     * Validation rules for the LAN Party Publishing Standard event fields.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function lppsRules(): array
    {
        return [
            'attendance_mode' => ['nullable', Rule::enum(AttendanceMode::class)],
            'syndication_status' => ['nullable', Rule::enum(EventSyndicationStatus::class)],
            'previous_start_date' => ['nullable', 'date'],
            'has_showers' => ['nullable', 'boolean'],
            'sleeping' => ['nullable', 'integer', 'min:0', 'max:15'],
            'alcohol_policy' => ['nullable', 'integer', 'min:0', 'max:15'],
            'smoking_policy' => ['nullable', 'integer', 'min:0', 'max:15'],
            'age_policy' => ['nullable', 'integer', 'min:0', 'max:15'],
            'food_policy' => ['nullable', 'integer', 'min:0', 'max:15'],
            'network_connection_mbps' => ['nullable', 'integer', 'min:0'],
            'internet_connection_mbps' => ['nullable', 'integer', 'min:0'],
            'wifi_connection_mbps' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
