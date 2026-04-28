<?php

use App\Domain\Policy\Actions\PublishPolicyVersion;
use App\Domain\Policy\Events\PolicyVersionPublished;
use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyVersion;
use App\Models\User;
use App\Support\StorageRole;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake(StorageRole::privateDiskName());
});

it('creates the first version with version_number=1 per locale', function (): void {
    $policy = Policy::factory()->create();
    $publisher = User::factory()->create();

    $version = app(PublishPolicyVersion::class)->execute(
        policy: $policy,
        content: '# Hello',
        isNonEditorial: false,
        publicStatement: null,
        publishedBy: $publisher,
        locale: 'en',
    );

    expect($version->version_number)->toBe(1)
        ->and($version->locale)->toBe('en')
        ->and($version->is_non_editorial_change)->toBeFalse()
        ->and($version->published_by_user_id)->toBe($publisher->id);
});

it('increments version_number per (policy, locale)', function (): void {
    $policy = Policy::factory()->create();
    $publisher = User::factory()->create();
    $action = app(PublishPolicyVersion::class);

    $action->execute($policy, '# v1', false, null, $publisher, 'en');
    $action->execute($policy, '# v2', false, null, $publisher, 'en');
    $de = $action->execute($policy, '# de-v1', false, null, $publisher, 'de');

    expect(PolicyVersion::where('policy_id', $policy->id)->where('locale', 'en')->count())->toBe(2)
        ->and(PolicyVersion::where('policy_id', $policy->id)->where('locale', 'en')->orderByDesc('version_number')->first()->version_number)->toBe(2)
        ->and($de->version_number)->toBe(1);
});

it('renders and stores a PDF for the published version', function (): void {
    $policy = Policy::factory()->create();
    $publisher = User::factory()->create();

    $version = app(PublishPolicyVersion::class)->execute(
        $policy,
        '# Some content',
        false,
        null,
        $publisher,
    );

    expect($version->pdf_path)->toBe("policy-versions/{$version->id}.pdf");
    Storage::disk(StorageRole::privateDiskName())->assertExists($version->pdf_path);
});

it('does NOT update required_acceptance_version_id on editorial publishes', function (): void {
    $policy = Policy::factory()->create();
    $publisher = User::factory()->create();

    $version = app(PublishPolicyVersion::class)->execute(
        $policy,
        '# Editorial fix',
        false,
        null,
        $publisher,
    );

    expect($policy->fresh()->required_acceptance_version_id)->toBeNull();
});

it('updates required_acceptance_version_id on non-editorial publishes', function (): void {
    $policy = Policy::factory()->create();
    $publisher = User::factory()->create();

    $version = app(PublishPolicyVersion::class)->execute(
        $policy,
        '# Material change',
        true,
        'We have updated our data sharing terms.',
        $publisher,
    );

    expect($policy->fresh()->required_acceptance_version_id)->toBe($version->id)
        ->and($version->is_non_editorial_change)->toBeTrue()
        ->and($version->public_statement)->toBe('We have updated our data sharing terms.');
});

it('drops public_statement on editorial publishes even if provided', function (): void {
    $policy = Policy::factory()->create();
    $publisher = User::factory()->create();

    $version = app(PublishPolicyVersion::class)->execute(
        $policy,
        '# Typo fix',
        false,
        'should not persist',
        $publisher,
    );

    expect($version->public_statement)->toBeNull();
});

it('dispatches PolicyVersionPublished with silent=true for editorial', function (): void {
    Event::fake([PolicyVersionPublished::class]);
    $policy = Policy::factory()->create();
    $publisher = User::factory()->create();

    app(PublishPolicyVersion::class)->execute($policy, '# x', false, null, $publisher);

    Event::assertDispatched(PolicyVersionPublished::class, fn ($event) => $event->silent === true && $event->isNonEditorial === false);
});

it('dispatches PolicyVersionPublished with silent=false for non-editorial', function (): void {
    Event::fake([PolicyVersionPublished::class]);
    $policy = Policy::factory()->create();
    $publisher = User::factory()->create();

    app(PublishPolicyVersion::class)->execute($policy, '# x', true, 'why', $publisher);

    Event::assertDispatched(PolicyVersionPublished::class, fn ($event) => $event->silent === false && $event->isNonEditorial === true);
});
