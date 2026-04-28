<?php

use App\Domain\Policy\Actions\PublishPolicyVersion;
use App\Domain\Policy\Events\PolicyPublished;
use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyLocaleDraft;
use App\Domain\Policy\Models\PolicyVersion;
use App\Models\User;
use App\Support\StorageRole;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake(StorageRole::privateDiskName());
});

function publishWithDrafts(Policy $policy, array $drafts, bool $isNonEditorial = false, ?string $statement = null): array
{
    foreach ($drafts as $locale => $content) {
        PolicyLocaleDraft::factory()->for($policy)->create([
            'locale' => $locale,
            'content' => $content,
        ]);
    }

    return app(PublishPolicyVersion::class)->execute(
        policy: $policy,
        isNonEditorial: $isNonEditorial,
        publicStatement: $statement,
        publishedBy: User::factory()->create(),
    );
}

it('creates the first version with version_number=1 across every locale draft', function (): void {
    $policy = Policy::factory()->create();
    $rows = publishWithDrafts($policy, ['en' => '# Hello', 'de' => '# Hallo']);

    expect(count($rows))->toBe(2);
    expect(array_unique(array_map(fn ($r) => $r->version_number, $rows)))->toBe([1]);
    expect(collect($rows)->pluck('locale')->sort()->values()->all())->toBe(['de', 'en']);
});

it('increments version_number across the policy, not per locale', function (): void {
    $policy = Policy::factory()->create();
    $publisher = User::factory()->create();

    PolicyLocaleDraft::factory()->for($policy)->create(['locale' => 'en', 'content' => '# v1']);
    app(PublishPolicyVersion::class)->execute($policy, false, null, $publisher);

    $policy->drafts()->where('locale', 'en')->update(['content' => '# v2']);
    PolicyLocaleDraft::factory()->for($policy)->create(['locale' => 'de', 'content' => '# de v2']);
    app(PublishPolicyVersion::class)->execute($policy, false, null, $publisher);

    $en = PolicyVersion::where('policy_id', $policy->id)->where('locale', 'en')->pluck('version_number')->all();
    $de = PolicyVersion::where('policy_id', $policy->id)->where('locale', 'de')->pluck('version_number')->all();

    expect($en)->toBe([1, 2]);
    expect($de)->toBe([2]);
});

it('renders and stores a PDF for every locale of the publish', function (): void {
    $policy = Policy::factory()->create();
    $rows = publishWithDrafts($policy, ['en' => '# en', 'de' => '# de']);

    foreach ($rows as $version) {
        expect($version->pdf_path)->toBe("policy-versions/{$version->id}.pdf");
        Storage::disk(StorageRole::privateDiskName())->assertExists($version->pdf_path);
    }
});

it('does NOT update required_acceptance_version_number on editorial publishes', function (): void {
    $policy = Policy::factory()->create();
    publishWithDrafts($policy, ['en' => '# Editorial fix']);

    expect($policy->fresh()->required_acceptance_version_number)->toBeNull();
});

it('updates required_acceptance_version_number on non-editorial publishes', function (): void {
    $policy = Policy::factory()->create();
    $rows = publishWithDrafts($policy, ['en' => '# major', 'de' => '# major'], isNonEditorial: true, statement: 'major change');

    expect($policy->fresh()->required_acceptance_version_number)->toBe(1);
    foreach ($rows as $version) {
        expect($version->is_non_editorial_change)->toBeTrue();
        expect($version->public_statement)->toBe('major change');
    }
});

it('drops public_statement on editorial publishes even if provided', function (): void {
    $policy = Policy::factory()->create();
    $rows = publishWithDrafts($policy, ['en' => '# Typo fix'], isNonEditorial: false, statement: 'should not persist');

    expect($rows[0]->public_statement)->toBeNull();
});

it('rejects publish when policy has no drafts', function (): void {
    $policy = Policy::factory()->create();
    $publisher = User::factory()->create();

    expect(fn () => app(PublishPolicyVersion::class)->execute($policy, false, null, $publisher))
        ->toThrow(RuntimeException::class, 'no locale drafts');
});

it('rejects publish when any draft is empty', function (): void {
    $policy = Policy::factory()->create();
    $publisher = User::factory()->create();
    PolicyLocaleDraft::factory()->for($policy)->create(['locale' => 'en', 'content' => '# present']);
    PolicyLocaleDraft::factory()->for($policy)->create(['locale' => 'de', 'content' => '']);

    expect(fn () => app(PublishPolicyVersion::class)->execute($policy, false, null, $publisher))
        ->toThrow(RuntimeException::class, 'draft for locale [de] is empty');

    expect(PolicyVersion::where('policy_id', $policy->id)->count())->toBe(0);
});

it('dispatches PolicyPublished with silent=true for editorial', function (): void {
    Event::fake([PolicyPublished::class]);
    $policy = Policy::factory()->create();
    publishWithDrafts($policy, ['en' => '# x']);

    Event::assertDispatched(PolicyPublished::class, fn ($event) => $event->silent === true && $event->isNonEditorial === false);
    Event::assertDispatchedTimes(PolicyPublished::class, 1);
});

it('dispatches PolicyPublished with silent=false for non-editorial', function (): void {
    Event::fake([PolicyPublished::class]);
    $policy = Policy::factory()->create();
    publishWithDrafts($policy, ['en' => '# x', 'de' => '# x'], isNonEditorial: true, statement: 'why');

    Event::assertDispatched(PolicyPublished::class, fn ($event) => $event->silent === false && $event->isNonEditorial === true && $event->versionNumber === 1);
    Event::assertDispatchedTimes(PolicyPublished::class, 1);
});
