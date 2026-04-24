<?php

return [
    'errors' => [
        'seat_plan_event_mismatch' => 'Der gewählte Sitzplan gehört nicht zur Veranstaltung dieses Tickets.',
        'seat_taken' => 'Dieser Sitzplatz wurde soeben von jemand anderem belegt. Bitte wähle einen anderen.',
        'seat_not_available' => 'Dieser Sitzplatz ist nicht auswählbar.',
        'seat_not_found' => 'Der gewählte Sitzplatz existiert nicht auf diesem Sitzplan.',
        'user_not_on_ticket' => 'Dieser Benutzer ist nicht diesem Ticket zugewiesen.',
        'block_category_forbidden' => 'Dieser Sitzplatz ist für deine Ticketkategorie nicht verfügbar. Bitte wähle einen Sitzplatz in einem Bereich, der deine Ticketkategorie zulässt.',
    ],

    'notifications' => [
        'invalidated' => [
            'subject' => 'Dein Sitzplatz für :event wurde freigegeben',
            'body' => 'Dein Sitzplatz :seat für :event ist dir nicht mehr zugewiesen, weil der Sitzplan von einem Administrator aktualisiert wurde.',
            'reason_seat_removed' => 'Der Sitzplatz wurde aus dem Plan entfernt.',
            'reason_category_mismatch' => 'Deine Ticketkategorie ist für diesen Bereich nicht mehr zulässig.',
            'action' => 'Neuen Sitzplatz wählen',
            'preferences_hint' => 'Du erhältst diese E-Mail, weil du ein Ticket für diese Veranstaltung hast. Du kannst Sitzplatz-E-Mails in deinen Benachrichtigungseinstellungen deaktivieren.',
        ],
    ],
];
