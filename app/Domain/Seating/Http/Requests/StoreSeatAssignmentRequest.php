<?php

namespace App\Domain\Seating\Http\Requests;

use App\Domain\Seating\Models\SeatPlan;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @see docs/mil-std-498/SRS.md SET-F-006
 */
class StoreSeatAssignmentRequest extends FormRequest
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
            'ticket_id' => ['required', 'integer', 'exists:tickets,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'seat_plan_id' => [
                'required',
                'integer',
                'exists:seat_plans,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $event = $this->route('event');
                    $eventId = is_object($event) ? $event->id : (int) $event;

                    $belongs = SeatPlan::query()
                        ->whereKey($value)
                        ->where('event_id', $eventId)
                        ->exists();

                    if (! $belongs) {
                        $fail(__('seating.errors.seat_plan_event_mismatch'));
                    }
                },
            ],
            'seat_id' => ['required', 'string', 'max:64'],
        ];
    }
}
