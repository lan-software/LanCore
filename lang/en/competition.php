<?php

return [
    'team' => [
        'join_request_sent' => 'Join request sent. The team captain will be notified.',
        'invite_sent' => 'Invite sent.',
        'left_disbanded' => 'You left the team. As the last member, the team has been disbanded.',
        'left' => 'You have left the team.',
        'deleted' => 'Team deleted.',
        'joined' => "You've joined :name!",
        'invite_declined' => 'Invite declined.',
    ],
    'notifications' => [
        'join_request' => [
            'subject' => 'Join request for :name',
            'greeting' => 'New join request',
            'body' => ':user wants to join your team :team.',
            'body_with_competition' => ':user wants to join your team :team in :competition.',
            'message_line' => 'Message: ":message"',
            'action' => 'Review Request',
        ],
        'join_request_resolved' => [
            'subject' => 'Join request :status — :name',
            'greeting_approved' => 'Welcome to the team!',
            'greeting_denied' => 'Request denied',
            'approved_line' => 'Your request to join :team has been approved.',
            'denied_line' => 'Your request to join :team has been denied.',
            'action_view_team' => 'View Team',
        ],
    ],
];
