<?php

use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\SignupRules\Support\SignupRuleEvaluator;
use App\Domain\Competition\SignupRules\Support\SignupRuleRegistry;
use App\Models\User;

function makeEvaluator(): SignupRuleEvaluator
{
    return new SignupRuleEvaluator(new SignupRuleRegistry);
}

it('treats an empty tree as satisfied', function () {
    $evaluator = makeEvaluator();
    $user = User::factory()->create(['steam_id_64' => null]);
    $competition = Competition::factory()->create();

    expect($evaluator->evaluate(null, $user, $competition)->satisfied)->toBeTrue();
    expect($evaluator->evaluate([], $user, $competition)->satisfied)->toBeTrue();
});

it('passes a single rule that is satisfied', function () {
    $evaluator = makeEvaluator();
    $user = User::factory()->create(['steam_id_64' => '76561198000000000']);
    $competition = Competition::factory()->create();

    $tree = ['type' => 'all', 'rules' => [
        ['type' => 'rule', 'key' => 'has_steam_account_linked'],
    ]];

    $result = $evaluator->evaluate($tree, $user, $competition);
    expect($result->satisfied)->toBeTrue();
    expect($result->unmetReasons)->toBe([]);
});

it('fails an unsatisfied AND group with its reason', function () {
    $evaluator = makeEvaluator();
    $user = User::factory()->create(['steam_id_64' => null]);
    $competition = Competition::factory()->create();

    $tree = ['type' => 'all', 'rules' => [
        ['type' => 'rule', 'key' => 'has_steam_account_linked'],
    ]];

    $result = $evaluator->evaluate($tree, $user, $competition);
    expect($result->satisfied)->toBeFalse();
    expect($result->unmetReasons)->toHaveCount(1);
    expect($result->unmetReasons[0]->ruleKey)->toBe('has_steam_account_linked');
});

it('AND group fails if any child fails', function () {
    $evaluator = makeEvaluator();
    $user = User::factory()->create(['steam_id_64' => '76561198000000000']);
    $competition = Competition::factory()->create();

    $tree = ['type' => 'all', 'rules' => [
        ['type' => 'rule', 'key' => 'has_steam_account_linked'],
        ['type' => 'rule', 'key' => 'has_seat'],
    ]];

    $result = $evaluator->evaluate($tree, $user, $competition);
    expect($result->satisfied)->toBeFalse();
    expect($result->unmetReasons[0]->ruleKey)->toBe('has_seat');
});

it('OR group passes if any child passes', function () {
    $evaluator = makeEvaluator();
    $user = User::factory()->create(['steam_id_64' => '76561198000000000']);
    $competition = Competition::factory()->create();

    $tree = ['type' => 'any', 'rules' => [
        ['type' => 'rule', 'key' => 'has_seat'],
        ['type' => 'rule', 'key' => 'has_steam_account_linked'],
    ]];

    $result = $evaluator->evaluate($tree, $user, $competition);
    expect($result->satisfied)->toBeTrue();
    expect($result->unmetReasons)->toBe([]);
});

it('OR group fails with all alternatives reported', function () {
    $evaluator = makeEvaluator();
    $user = User::factory()->create(['steam_id_64' => null]);
    $competition = Competition::factory()->create();

    $tree = ['type' => 'any', 'rules' => [
        ['type' => 'rule', 'key' => 'has_steam_account_linked'],
        ['type' => 'rule', 'key' => 'has_seat'],
    ]];

    $result = $evaluator->evaluate($tree, $user, $competition);
    expect($result->satisfied)->toBeFalse();
    expect(collect($result->unmetReasons)->pluck('ruleKey')->all())
        ->toEqual(['has_steam_account_linked', 'has_seat']);
});

it('supports nested groups (AND of an OR)', function () {
    $evaluator = makeEvaluator();
    $user = User::factory()->create(['steam_id_64' => '76561198000000000']);
    $competition = Competition::factory()->create();

    // satisfied: steam linked AND (seat OR steam) -> both legs of AND pass
    $tree = ['type' => 'all', 'rules' => [
        ['type' => 'rule', 'key' => 'has_steam_account_linked'],
        ['type' => 'any', 'rules' => [
            ['type' => 'rule', 'key' => 'has_seat'],
            ['type' => 'rule', 'key' => 'has_steam_account_linked'],
        ]],
    ]];

    expect($evaluator->evaluate($tree, $user, $competition)->satisfied)->toBeTrue();
});

it('ignores unknown rule keys (treats as satisfied)', function () {
    $evaluator = makeEvaluator();
    $user = User::factory()->create();
    $competition = Competition::factory()->create();

    $tree = ['type' => 'all', 'rules' => [
        ['type' => 'rule', 'key' => 'definitely_not_a_real_rule'],
    ]];

    expect($evaluator->evaluate($tree, $user, $competition)->satisfied)->toBeTrue();
});
