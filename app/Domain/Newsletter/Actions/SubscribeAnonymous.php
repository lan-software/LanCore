<?php

namespace App\Domain\Newsletter\Actions;

use App\Domain\Newsletter\Clients\ListmonkClient;
use App\Domain\Newsletter\Models\NewsletterList;

/**
 * Public-form-only path. Anonymous subscribers do NOT get a `User` row;
 * their entire identity lives inside Listmonk. We therefore never touch
 * `newsletter_list_user` here.
 *
 * @see docs/mil-std-498/SRS.md NLT-F-006
 */
class SubscribeAnonymous
{
    public function __construct(private readonly ListmonkClient $listmonk) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(string $email, ?string $name, NewsletterList $list, ?bool $preconfirm = null): array
    {
        $preconfirm ??= (bool) config('listmonk.preconfirm_subscriptions');

        return $this->listmonk->upsertSubscriber(
            email: $email,
            name: $name,
            listIds: [$list->listmonk_id],
            preconfirm: $preconfirm,
        );
    }
}
