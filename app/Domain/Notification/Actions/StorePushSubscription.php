<?php

namespace App\Domain\Notification\Actions;

use App\Domain\Notification\Models\PushSubscription;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md NTF-F-003
 */
class StorePushSubscription
{
    /**
     * @param  array{endpoint: string, public_key: string, auth_token: string, content_encoding?: string}  $data
     */
    public function execute(User $user, array $data): PushSubscription
    {
        return PushSubscription::updateOrCreate(
            [
                'user_id' => $user->id,
                'endpoint' => $data['endpoint'],
            ],
            [
                'public_key' => $data['public_key'],
                'auth_token' => $data['auth_token'],
                'content_encoding' => $data['content_encoding'] ?? 'aesgcm',
            ]
        );
    }
}
