<?php

namespace App\Domain\Policy\Notifications;

use App\Domain\Policy\Models\PolicyVersion;
use App\Support\StorageRole;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies a single user that a non-editorial version of a Policy they
 * previously accepted has been published. The published PDF is attached
 * and the operator's public_statement is rendered inline.
 *
 * Sent only when `PolicyVersionPublished::$silent` is false (i.e. the
 * publish was non-editorial).
 *
 * @see docs/mil-std-498/SSS.md CAP-POL-005
 * @see docs/mil-std-498/SRS.md POL-F-015, POL-F-016
 */
class PolicyVersionPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly PolicyVersion $version) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $version = $this->version->loadMissing('policy');
        $policy = $version->policy;

        $message = (new MailMessage)
            ->subject(__('policies.notifications.version_published.subject', [
                'name' => $policy->name,
            ]))
            ->greeting(__('policies.notifications.version_published.greeting', [
                'name' => $notifiable->name ?? '',
            ]))
            ->line(__('policies.notifications.version_published.intro', [
                'name' => $policy->name,
                'version' => $version->version_number,
            ]));

        if ($version->public_statement) {
            $message
                ->line(__('policies.notifications.version_published.statement_heading'))
                ->line($version->public_statement);
        }

        $message
            ->line(__('policies.notifications.version_published.outro'))
            ->action(
                __('policies.notifications.version_published.action'),
                url(route('policies.show', ['policy' => $policy->key], false)),
            );

        if (property_exists($notifiable, 'locale') && $notifiable->locale) {
            $message->locale($notifiable->locale);
        }

        if ($version->pdf_path) {
            $disk = StorageRole::private();

            if ($disk->exists($version->pdf_path)) {
                $filename = sprintf(
                    '%s-v%d.pdf',
                    $policy->key,
                    $version->version_number,
                );

                $message->attachData(
                    $disk->get($version->pdf_path),
                    $filename,
                    ['mime' => 'application/pdf'],
                );
            }
        }

        return $message;
    }
}
