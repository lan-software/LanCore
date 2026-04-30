<?php

namespace App\Domain\DataLifecycle\Anonymizers;

use App\Domain\DataLifecycle\Anonymizers\Contracts\DomainAnonymizer;
use App\Domain\DataLifecycle\Providers\DataLifecycleServiceProvider;

/**
 * Singleton registry of {@see DomainAnonymizer} implementations, populated by
 * {@see DataLifecycleServiceProvider}.
 *
 * Order is preserved — anonymizers are run in registration order. Place the
 * users-row scrub last so other anonymizers can still query the user.
 */
final class DomainAnonymizerRegistry
{
    /** @var list<DomainAnonymizer> */
    private array $anonymizers = [];

    public function register(DomainAnonymizer $anonymizer): void
    {
        $this->anonymizers[] = $anonymizer;
    }

    /**
     * @return list<DomainAnonymizer>
     */
    public function all(): array
    {
        return $this->anonymizers;
    }
}
