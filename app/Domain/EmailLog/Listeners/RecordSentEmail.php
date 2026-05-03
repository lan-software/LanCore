<?php

namespace App\Domain\EmailLog\Listeners;

use App\Domain\EmailLog\Enums\EmailMessageStatus;
use App\Domain\EmailLog\Models\EmailMessage;
use Illuminate\Mail\Events\MessageSent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\Headers;

/**
 * Persists every sent email into the email_messages table for the
 * admin Email Overview. Source classification (which Notification or
 * Mailable triggered the mail) is read from the optional
 * `X-LanCore-Source` and `X-LanCore-Source-Label` headers — Mailables
 * and Notifications opt in by adding those via withSymfonyMessage().
 */
class RecordSentEmail
{
    public function handle(MessageSent $event): void
    {
        $email = $event->message;

        if (! $email instanceof Email) {
            return;
        }

        $headers = $email->getHeaders();
        $source = $this->headerValue($headers, 'X-LanCore-Source');
        $sourceLabel = $this->headerValue($headers, 'X-LanCore-Source-Label');

        EmailMessage::create([
            'message_id' => $headers->has('Message-ID')
                ? $headers->get('Message-ID')->getBodyAsString()
                : null,
            'mailer' => $event->data['mailer'] ?? config('mail.default'),
            'from_address' => $this->firstAddress($email->getFrom())?->getAddress(),
            'from_name' => $this->firstAddress($email->getFrom())?->getName() ?: null,
            'to_addresses' => $this->mapAddresses($email->getTo()),
            'cc_addresses' => $this->mapAddresses($email->getCc()),
            'bcc_addresses' => $this->mapAddresses($email->getBcc()),
            'subject' => $email->getSubject(),
            'html_body' => $email->getHtmlBody(),
            'text_body' => $email->getTextBody(),
            'headers' => $this->serializeHeaders($headers),
            'tags' => null,
            'status' => EmailMessageStatus::Sent,
            'source' => $source,
            'source_label' => $sourceLabel,
            'sent_at' => now(),
        ]);
    }

    private function headerValue(Headers $headers, string $name): ?string
    {
        return $headers->has($name)
            ? $headers->get($name)->getBodyAsString()
            : null;
    }

    /**
     * @param  array<int, Address>  $addresses
     */
    private function firstAddress(array $addresses): ?Address
    {
        return $addresses[0] ?? null;
    }

    /**
     * @param  array<int, Address>  $addresses
     * @return array<int, array{address: string, name: string|null}>
     */
    private function mapAddresses(array $addresses): array
    {
        return array_map(fn (Address $address) => [
            'address' => $address->getAddress(),
            'name' => $address->getName() ?: null,
        ], $addresses);
    }

    /**
     * @return array<string, string>
     */
    private function serializeHeaders(Headers $headers): array
    {
        $out = [];

        foreach ($headers->all() as $header) {
            $out[$header->getName()] = $header->getBodyAsString();
        }

        return $out;
    }
}
