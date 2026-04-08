<?php

use App\Models\User;
use Illuminate\Support\Facades\Config;

it('exposes the demo banner shared prop when demo mode is enabled', function (): void {
    Config::set('app.demo', true);
    Config::set('app.demo_banner_message', 'Demo message');
    Config::set('app.demo_mailpit_url', 'https://mail.example');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertInertia(fn ($page) => $page
            ->where('demoBanner.message', 'Demo message')
            ->where('demoBanner.mailpit_url', 'https://mail.example')
        );
});

it('returns null demo banner shared prop when demo mode is disabled', function (): void {
    Config::set('app.demo', false);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertInertia(fn ($page) => $page->where('demoBanner', null));
});
