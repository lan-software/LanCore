<?php

use App\Domain\Chat\Actions\DeleteMessage;
use App\Domain\Chat\Actions\PostMessage;
use App\Domain\Chat\Contracts\RoomPolicy;
use App\Domain\Chat\Gdpr\ChatDataSource;
use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Models\ChatRoomMembership;
use App\Domain\Chat\Services\ChatService;
use App\Domain\Policy\Gdpr\GdprExportContext;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

class GdprAllowAllPolicy implements RoomPolicy
{
    public function canView(User $user, ChatRoom $room): bool
    {
        return true;
    }

    public function canPost(User $user, ChatRoom $room): bool
    {
        return true;
    }

    public function canModerate(User $user, ChatRoom $room): bool
    {
        return true;
    }

    public function describe(ChatRoom $room): string
    {
        return 'allow-all';
    }
}

beforeEach(function (): void {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);

    $this->subject = User::factory()->create(['username' => 'subject']);
    $this->other = User::factory()->create(['username' => 'other']);
    $this->moderator = User::factory()->create();

    $this->service = app(ChatService::class);
    $this->source = app(ChatDataSource::class);
});

it('exports the subject\'s authored messages including soft-deleted ones', function (): void {
    $room = $this->service->ensureRoom('gdpr:'.uniqid(), new GdprAllowAllPolicy);

    $msg1 = app(PostMessage::class)->execute($this->subject, $room, 'kept message');
    $msg2 = app(PostMessage::class)->execute($this->subject, $room, 'deleted message');
    app(DeleteMessage::class)->execute($this->moderator, $msg2);

    $ctx = new GdprExportContext($this->subject, new DateTimeImmutable);
    $result = $this->source->for($this->subject, $ctx);

    $bodies = collect($result->records['authored_messages'])->pluck('body')->all();

    expect($bodies)->toContain('kept message')->toContain('deleted message');
});

it('excludes other users\' messages from the same room', function (): void {
    $room = $this->service->ensureRoom('gdpr:'.uniqid(), new GdprAllowAllPolicy);

    app(PostMessage::class)->execute($this->other, $room, 'someone else wrote this');
    app(PostMessage::class)->execute($this->subject, $room, 'subject wrote this');

    $ctx = new GdprExportContext($this->subject, new DateTimeImmutable);
    $result = $this->source->for($this->subject, $ctx);

    $bodies = collect($result->records['authored_messages'])->pluck('body')->all();
    expect($bodies)->toContain('subject wrote this');
    expect($bodies)->not->toContain('someone else wrote this');
});

it('includes the subject\'s room memberships with room key + title', function (): void {
    $room = $this->service->ensureRoom('gdpr:'.uniqid(), new GdprAllowAllPolicy, ['title' => 'My Match Room']);
    ChatRoomMembership::create([
        'room_id' => $room->id,
        'user_id' => $this->subject->id,
        'joined_at' => now(),
    ]);

    $ctx = new GdprExportContext($this->subject, new DateTimeImmutable);
    $result = $this->source->for($this->subject, $ctx);

    expect($result->records['room_memberships'])->toHaveCount(1);
    expect($result->records['room_memberships'][0]['room_title'])->toBe('My Match Room');
});

it('obfuscates other users in the mentions list', function (): void {
    $room = $this->service->ensureRoom('gdpr:'.uniqid(), new GdprAllowAllPolicy);

    app(PostMessage::class)->execute($this->subject, $room, 'hey @other');

    $ctx = new GdprExportContext($this->subject, new DateTimeImmutable);
    $result = $this->source->for($this->subject, $ctx);

    $mentions = $result->records['authored_messages'][0]['mentions_json'];
    expect($mentions)->toBe([$ctx->obfuscateUser($this->other->id, 'mentioned')]);
});

it('is registered as a discoverable GDPR source', function (): void {
    expect($this->source->key())->toBe('chat');
    expect($this->source->label())->toBeString();
});
