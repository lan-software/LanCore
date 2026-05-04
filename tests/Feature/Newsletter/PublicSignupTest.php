<?php

use App\Domain\Newsletter\Models\NewsletterList;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function (): void {
    config()->set('listmonk.enabled', true);
    config()->set('listmonk.base_url', 'https://listmonk.test');
    config()->set('listmonk.username', 'admin');
    config()->set('listmonk.password', 'secret');

    RateLimiter::clear('newsletter-signup');
});

it('rejects an empty email', function (): void {
    $this->post('/newsletter/subscribe', ['email' => ''])
        ->assertSessionHasErrors('email');
});

it('returns a friendly error when no public list is configured', function (): void {
    $this->post('/newsletter/subscribe', ['email' => 'fan@example.com'])
        ->assertRedirect()
        ->assertSessionHasErrors('email');
});

it('subscribes an anonymous email through the public form', function (): void {
    NewsletterList::factory()->defaultPublic()->create(['listmonk_id' => 11]);

    Http::fake([
        'listmonk.test/api/subscribers*' => Http::sequence()
            ->push(['data' => ['results' => [], 'total' => 0, 'page' => 1, 'per_page' => 1]], 200)
            ->push(['data' => ['id' => 5, 'email' => 'fan@example.com', 'lists' => [['id' => 11]]]], 200),
    ]);

    $this->post('/newsletter/subscribe', [
        'email' => 'fan@example.com',
        'name' => 'Super Fan',
    ])->assertRedirect();
});
