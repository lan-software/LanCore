<?php

use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyVersion;

it('lists active policies with their current version in the user locale', function (): void {
    app()->setLocale('en');

    $policy = Policy::factory()->create();
    PolicyVersion::factory()->for($policy)->create([
        'version_number' => 1,
        'locale' => 'en',
        'published_at' => now()->subDay(),
    ]);
    PolicyVersion::factory()->for($policy)->create([
        'version_number' => 1,
        'locale' => 'de',
        'published_at' => now()->subDay(),
    ]);

    $this->get('/legal')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('legal/Index')
            ->has('policies', 1)
            ->where('policies.0.current_version.locale', 'en')
        );
});

it('falls back to the earliest-created locale row when the user locale has no version', function (): void {
    app()->setLocale('fr');

    $policy = Policy::factory()->create();
    PolicyVersion::factory()->for($policy)->create([
        'version_number' => 1,
        'locale' => 'en',
    ]);
    PolicyVersion::factory()->for($policy)->create([
        'version_number' => 1,
        'locale' => 'de',
    ]);

    $this->get('/legal')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('policies.0.current_version.locale', 'en')
        );
});

it('returns null current_version when the policy has no published versions', function (): void {
    Policy::factory()->create();

    $this->get('/legal')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('policies.0.current_version', null)
        );
});
