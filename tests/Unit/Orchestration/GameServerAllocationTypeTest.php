<?php

use App\Domain\Orchestration\Enums\GameServerAllocationType;

it('returns correct priorities for allocation types', function () {
    expect(GameServerAllocationType::Competition->priority())->toBe(1);
    expect(GameServerAllocationType::Flexible->priority())->toBe(2);
    expect(GameServerAllocationType::Casual->priority())->toBe(3);
});

it('orders allocation types by priority', function () {
    $types = GameServerAllocationType::cases();

    $sorted = collect($types)->sortBy(fn ($t) => $t->priority())->values();

    expect($sorted[0])->toBe(GameServerAllocationType::Competition);
    expect($sorted[1])->toBe(GameServerAllocationType::Flexible);
    expect($sorted[2])->toBe(GameServerAllocationType::Casual);
});
