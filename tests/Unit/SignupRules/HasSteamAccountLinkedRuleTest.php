<?php

use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\SignupRules\Rules\HasSteamAccountLinkedRule;
use App\Models\User;

it('is satisfied when the user has a steam id', function () {
    $user = User::factory()->create(['steam_id_64' => '76561198000000000']);
    $competition = Competition::factory()->create();
    $rule = new HasSteamAccountLinkedRule;

    expect($rule->isSatisfiedBy($user, $competition))->toBeTrue();
});

it('is not satisfied when the user has no steam id', function () {
    $user = User::factory()->create(['steam_id_64' => null]);
    $competition = Competition::factory()->create();
    $rule = new HasSteamAccountLinkedRule;

    expect($rule->isSatisfiedBy($user, $competition))->toBeFalse();
});

it('returns a reason with the canonical message key', function () {
    $user = User::factory()->create(['steam_id_64' => null]);
    $competition = Competition::factory()->create();
    $rule = new HasSteamAccountLinkedRule;

    $reason = $rule->reason($user, $competition);

    expect($reason->ruleKey)->toBe('has_steam_account_linked');
    expect($reason->messageKey)->toBe(
        'competitions.signupRules.reasons.has_steam_account_linked',
    );
});
