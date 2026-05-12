<?php

return [

    'errors' => [
        'room_not_postable' => 'You cannot post in this room. It is either write-locked or archived.',
        'post_unauthorized' => 'You are not allowed to post in this room.',
        'user_muted' => 'You are muted in this room.',
        'rate_limit_short' => 'You are sending messages too quickly. Slow down.',
        'rate_limit_long' => 'You have hit the per-minute message limit. Try again in a moment.',
        'duplicate_message' => 'You just sent that exact message — try saying something else.',
    ],

    'message' => [
        'deleted_placeholder' => '(message removed by a moderator)',
    ],

    'notifications' => [
        'mention' => [
            'subject' => 'You were mentioned in a chat',
            'greeting' => 'Hi :name,',
            'intro' => ':username mentioned you:',
            'push_title' => 'New chat mention',
        ],
    ],

];
