<?php

return [
    'errors' => [
        'seat_plan_event_mismatch' => 'The chosen seat plan does not belong to this ticket\'s event.',
        'seat_taken' => 'That seat was just taken by someone else. Please pick another.',
        'seat_not_available' => 'That seat is not available for selection.',
        'seat_not_found' => 'The selected seat does not exist on this seat plan.',
        'user_not_on_ticket' => 'That user is not assigned to this ticket.',
        'block_category_forbidden' => 'This seat is not available for your ticket category. Please pick a seat in a block that accepts your ticket category.',
    ],

    'notifications' => [
        'invalidated' => [
            'subject' => 'Your seat for :event has been released',
            'body' => 'Your seat :seat for :event is no longer assigned to you because the seat plan was updated by an administrator.',
            'reason_seat_removed' => 'The seat itself was removed from the plan.',
            'reason_category_mismatch' => 'Your ticket category no longer qualifies for that section of the venue.',
            'action' => 'Pick a new seat',
            'preferences_hint' => 'You received this email because you have a ticket for this event. You can disable seating emails in your notification preferences.',
        ],
    ],
];
