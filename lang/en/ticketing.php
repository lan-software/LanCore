<?php

return [
    'admin' => [
        'token_rotated' => 'Ticket token rotated.',
    ],
    'notifications' => [
        'token_rotated' => [
            'subject' => 'Your ticket QR code has been updated',
            'body' => 'The QR code for your ticket #:id (:event) has been regenerated.',
            'copies_invalid' => 'Any previously printed or saved copies are no longer valid.',
            'action' => 'View my ticket',
            'instructions' => 'You can either re-download the PDF or show the live QR from "My Tickets" at the entrance.',
            'reason_user_added' => 'A user was added to this ticket, so the QR code has been refreshed.',
            'reason_user_removed' => 'A user was removed from this ticket, so the QR code has been refreshed.',
            'reason_manager_changed' => 'The ticket manager was changed, so the QR code has been refreshed.',
            'reason_user_requested' => 'You (or the ticket manager) requested a QR refresh.',
            'reason_default' => 'The QR code has been refreshed.',
        ],
    ],
];
