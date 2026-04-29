<?php

namespace App\Domain\Event\Actions;

use App\Domain\Event\Models\Event;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event as CalendarEvent;

/**
 * Renders a published Event as an RFC 5545 iCalendar payload.
 *
 * @see docs/mil-std-498/SSS.md CAP-EVT-007
 * @see docs/mil-std-498/SRS.md EVT-F-012
 */
class BuildEventIcal
{
    public function execute(Event $event): string
    {
        $event->loadMissing('venue.address');

        $calendarEvent = CalendarEvent::create($event->name)
            ->uniqueIdentifier(sprintf('event-%d@%s', $event->id, parse_url(config('app.url'), PHP_URL_HOST) ?: 'lancore.local'))
            ->startsAt($event->start_date)
            ->endsAt($event->end_date ?? $event->start_date);

        if (! empty($event->description)) {
            $calendarEvent->description($event->description);
        }

        $location = $this->formatLocation($event);

        if ($location !== null) {
            $calendarEvent->address($location, $event->venue?->name);
        }

        return Calendar::create($event->name)
            ->productIdentifier(config('app.name', 'LanCore').' // EN')
            ->event($calendarEvent)
            ->get();
    }

    private function formatLocation(Event $event): ?string
    {
        $venue = $event->venue;

        if ($venue === null) {
            return null;
        }

        $address = $venue->address;

        if ($address === null) {
            return $venue->name ?: null;
        }

        $parts = array_filter([
            $venue->name,
            $address->street,
            trim(($address->zip_code ?? '').' '.($address->city ?? '')),
            $address->state,
            $address->country,
        ], fn ($part) => is_string($part) && trim($part) !== '');

        return $parts === [] ? null : implode(', ', $parts);
    }
}
