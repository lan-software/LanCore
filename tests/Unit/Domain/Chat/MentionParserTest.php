<?php

use App\Domain\Chat\Support\MentionParser;
use App\Models\User;

beforeEach(function (): void {
    $this->parser = app(MentionParser::class);
});

it('parses a single mention', function (): void {
    User::factory()->create(['username' => 'alice']);

    expect($this->parser->parse('hello @alice'))
        ->toBe([User::where('username', 'alice')->value('id')]);
});

it('parses mentions case-insensitively', function (): void {
    $user = User::factory()->create(['username' => 'bob']);

    expect($this->parser->parse('hi @BOB'))->toBe([$user->id]);
});

it('deduplicates repeated mentions in the same body', function (): void {
    $user = User::factory()->create(['username' => 'carol']);

    expect($this->parser->parse('@carol @carol @CAROL hey'))->toBe([$user->id]);
});

it('returns an empty array when nobody is mentioned', function (): void {
    expect($this->parser->parse('hello world'))->toBe([]);
});

it('ignores @-strings inside email-like sequences', function (): void {
    expect($this->parser->parse('email me at foo@example.com'))->toBe([]);
});

it('ignores unknown usernames', function (): void {
    expect($this->parser->parse('@noonehere'))->toBe([]);
});

it('returns multiple distinct mentions in order of discovery', function (): void {
    $a = User::factory()->create(['username' => 'dave']);
    $b = User::factory()->create(['username' => 'eve']);

    $ids = $this->parser->parse('@dave and @eve are here');

    expect($ids)->toContain($a->id)->toContain($b->id)->toHaveCount(2);
});

it('rejects usernames shorter than 3 characters', function (): void {
    User::factory()->create(['username' => 'al']);

    expect($this->parser->parse('hey @al'))->toBe([]);
});
