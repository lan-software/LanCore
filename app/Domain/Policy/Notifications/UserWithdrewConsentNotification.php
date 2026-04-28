<?php

namespace App\Domain\Policy\Notifications;

use App\Domain\Policy\Models\PolicyAcceptance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to platform admins (users holding ManagePolicies) when a user
 * withdraws consent for a Policy via the settings UI.
 *
 * @see docs/mil-std-498/SSS.md CAP-POL-008
 * @see docs/mil-std-498/SRS.md POL-F-014
 */
class UserWithdrewConsentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly PolicyAcceptance $acceptance) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $acceptance = $this->acceptance->loadMissing('user', 'version.policy');
        $policy = $acceptance->version->policy;
        $userLabel = $acceptance->user?->name ?? '#'.$acceptance->user_id;

        $message = (new MailMessage)
            ->subject(__('policies.notifications.consent_withdrawn.subject', [
                'name' => $userLabel,
                'policy' => $policy->name,
            ]))
            ->line(__('policies.notifications.consent_withdrawn.intro', [
                'name' => $userLabel,
                'policy' => $policy->name,
            ]))
            ->line(__('policies.notifications.consent_withdrawn.withdrawn_at', [
                'date' => $acceptance->withdrawn_at?->toDateTimeString() ?? '',
            ]));

        if ($acceptance->withdrawn_reason) {
            $message
                ->line(__('policies.notifications.consent_withdrawn.reason_heading'))
                ->line($acceptance->withdrawn_reason);
        }

        $message->line(__('policies.notifications.consent_withdrawn.outro'));

        if (property_exists($notifiable, 'locale') && $notifiable->locale) {
            $message->locale($notifiable->locale);
        }

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $acceptance = $this->acceptance->loadMissing('user', 'version.policy');

        return [
            'acceptance_id' => $acceptance->id,
            'user_id' => $acceptance->user_id,
            'user_name' => $acceptance->user?->name,
            'policy_id' => $acceptance->version?->policy?->id,
            'policy_key' => $acceptance->version?->policy?->key,
            'policy_name' => $acceptance->version?->policy?->name,
            'version_number' => $acceptance->version?->version_number,
            'withdrawn_at' => $acceptance->withdrawn_at?->toIso8601String(),
            'reason' => $acceptance->withdrawn_reason,
        ];
    }
}
