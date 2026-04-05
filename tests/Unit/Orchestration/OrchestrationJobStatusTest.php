<?php

use App\Domain\Orchestration\Enums\OrchestrationJobStatus;

it('identifies terminal statuses', function () {
    expect(OrchestrationJobStatus::Completed->isTerminal())->toBeTrue();
    expect(OrchestrationJobStatus::Failed->isTerminal())->toBeTrue();
    expect(OrchestrationJobStatus::Cancelled->isTerminal())->toBeTrue();

    expect(OrchestrationJobStatus::Pending->isTerminal())->toBeFalse();
    expect(OrchestrationJobStatus::SelectingServer->isTerminal())->toBeFalse();
    expect(OrchestrationJobStatus::Deploying->isTerminal())->toBeFalse();
    expect(OrchestrationJobStatus::Active->isTerminal())->toBeFalse();
});

it('defines valid transitions from pending', function () {
    $transitions = OrchestrationJobStatus::Pending->allowedTransitions();

    expect($transitions)->toContain(OrchestrationJobStatus::SelectingServer);
    expect($transitions)->toContain(OrchestrationJobStatus::Failed);
    expect($transitions)->toContain(OrchestrationJobStatus::Cancelled);
    expect($transitions)->not->toContain(OrchestrationJobStatus::Active);
});

it('allows failed to transition to pending for retry', function () {
    expect(OrchestrationJobStatus::Failed->canTransitionTo(OrchestrationJobStatus::Pending))->toBeTrue();
    expect(OrchestrationJobStatus::Failed->canTransitionTo(OrchestrationJobStatus::Cancelled))->toBeTrue();
});

it('does not allow transitions from terminal states except failed', function () {
    expect(OrchestrationJobStatus::Completed->allowedTransitions())->toBeEmpty();
    expect(OrchestrationJobStatus::Cancelled->allowedTransitions())->toBeEmpty();
});

it('validates transition check', function () {
    expect(OrchestrationJobStatus::Active->canTransitionTo(OrchestrationJobStatus::Completed))->toBeTrue();
    expect(OrchestrationJobStatus::Active->canTransitionTo(OrchestrationJobStatus::Pending))->toBeFalse();
});
