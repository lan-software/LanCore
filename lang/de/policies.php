<?php

return [
    'flash' => [
        'created' => 'Richtlinie angelegt.',
        'updated' => 'Richtlinie aktualisiert.',
        'archived' => 'Richtlinie archiviert.',
        'type_created' => 'Richtlinien-Typ angelegt.',
        'type_updated' => 'Richtlinien-Typ aktualisiert.',
        'type_deleted' => 'Richtlinien-Typ gelöscht.',
        'type_in_use' => 'Dieser Richtlinien-Typ wird noch von mindestens einer Richtlinie verwendet und kann nicht gelöscht werden.',
        'version_published' => 'Version veröffentlicht.',
    ],
    'versions' => [
        'flash' => [
            'published' => 'Version veröffentlicht.',
        ],
    ],
    'drafts' => [
        'flash' => [
            'added' => 'Locale-Entwurf hinzugefügt.',
            'saved' => 'Entwurf gespeichert.',
            'removed' => 'Locale-Entwurf entfernt.',
        ],
        'errors' => [
            'cannot_remove_last_locale' => 'Eine Richtlinie muss mindestens einen Sprach-Entwurf haben.',
        ],
    ],
    'consent' => [
        'withdraw' => [
            'success' => 'Deine Einwilligung wurde widerrufen.',
            'no_active_acceptance' => 'Für diese Richtlinie wurde keine aktive Einwilligung gefunden.',
        ],
    ],
    'notifications' => [
        'version_published' => [
            'subject' => 'Neue Version von :name — bitte prüfen',
            'greeting' => 'Hallo :name,',
            'intro' => 'Eine neue Version von „:name" (v:version) wurde veröffentlicht. Der neue Text liegt als PDF im Anhang.',
            'statement_heading' => 'Mitteilung des Betreibers:',
            'outro' => 'Beim nächsten Besuch wirst du gebeten, die neue Version zu bestätigen, bevor du fortfahren kannst.',
            'action' => 'Richtlinie öffnen',
        ],
        'consent_withdrawn' => [
            'subject' => ':name hat die Einwilligung zu :policy widerrufen',
            'intro' => 'Der Nutzer :name hat die Einwilligung zur Richtlinie „:policy" widerrufen.',
            'withdrawn_at' => 'Widerrufen am: :date',
            'reason_heading' => 'Angegebener Grund:',
            'outro' => 'Der Datensatz bleibt zu Auditzwecken erhalten; nur die Widerrufsspalten wurden gesetzt.',
        ],
    ],
];
