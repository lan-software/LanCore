<?php

namespace App\Domain\Publishing\Http\Resources;

use App\Domain\Event\Models\Event;
use App\Domain\Publishing\Actions\BuildLanPartyDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Maps an {@see Event} to a LAN Party Publishing
 * Standard v2 event object. Only published events with a venue are emitted
 * (see {@see BuildLanPartyDocument}).
 *
 * @mixin Event
 *
 * @see docs/mil-std-498/SRS.md PUB-F-002, PUB-F-005
 */
class LppsEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_filter([
            'apiVersion' => 2,
            'apiType' => 'Event',
            'publisherUniqueId' => $this->id,
            'name' => $this->name,
            'url' => route('events.public.show', $this->resource),
            'eventStatus' => $this->syndication_status?->schemaOrgUrl(),
            'eventAttendanceMode' => $this->attendance_mode?->value,
            'startDate' => $this->start_date?->format('Y-m-d\TH:i:s'),
            'endDate' => $this->end_date?->format('Y-m-d\TH:i:s'),
            'previousStartDate' => $this->previous_start_date?->format('Y-m-d\TH:i:s'),
            'maximumAttendeeCapacity' => $this->seat_capacity,
            'remainingAttendeeCapacity' => $this->seat_capacity !== null ? $this->remainingSeatCapacity() : null,
            'tickets' => LppsTicketResource::collection($this->ticketTypes)->resolve(),
            'sleeping' => $this->sleeping,
            'hasShowers' => $this->has_showers,
            'alcoholPolicy' => $this->alcohol_policy,
            'smokingPolicy' => $this->smoking_policy,
            'agePolicy' => $this->age_policy,
            'foodPolicy' => $this->food_policy,
            'networkConnectionMbps' => $this->network_connection_mbps,
            'internetConnectionMbps' => $this->internet_connection_mbps,
            'wifiConnectionMbps' => $this->wifi_connection_mbps,
            'description' => $this->description,
        ], fn (mixed $value): bool => $value !== null);
    }
}
