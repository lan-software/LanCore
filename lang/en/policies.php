<?php

return [
    'flash' => [
        'created' => 'Policy created.',
        'updated' => 'Policy updated.',
        'archived' => 'Policy archived.',
        'type_created' => 'Policy type created.',
        'type_updated' => 'Policy type updated.',
        'type_deleted' => 'Policy type deleted.',
        'type_in_use' => 'This policy type is still referenced by one or more policies and cannot be deleted.',
        'version_published' => 'Version published.',
    ],
    'consent' => [
        'withdraw' => [
            'success' => 'Your consent has been withdrawn.',
            'no_active_acceptance' => 'No active consent was found for this policy.',
        ],
    ],
    'notifications' => [
        'version_published' => [
            'subject' => 'New version of :name — please review',
            'greeting' => 'Hello :name,',
            'intro' => 'A new version of ":name" (v:version) has been published. The new text is attached to this email as a PDF.',
            'statement_heading' => 'From the operator:',
            'outro' => 'On your next visit you will be asked to confirm the new version before continuing.',
            'action' => 'Open policy',
        ],
        'consent_withdrawn' => [
            'subject' => ':name withdrew consent for :policy',
            'intro' => 'User :name has withdrawn consent for the policy ":policy".',
            'withdrawn_at' => 'Withdrawn at: :date',
            'reason_heading' => 'Reason given:',
            'outro' => 'The acceptance row is preserved for audit purposes; only the withdrawal columns have been set.',
        ],
    ],
];
