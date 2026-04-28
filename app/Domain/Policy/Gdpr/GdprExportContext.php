<?php

namespace App\Domain\Policy\Gdpr;

use App\Models\User;
use DateTimeImmutable;

/**
 * Per-export run state. Builds a deterministic pseudonym table for
 * "other users" — `user_a`, `user_b`, … — never persisted, never
 * reverse-mappable in the export. Records the manifest data only.
 *
 * The pseudonym for a given input is stable within a single export
 * run, but distinct runs produce distinct mappings.
 *
 * @see docs/mil-std-498/SRS.md GDPR-F-005
 */
final class GdprExportContext
{
    /**
     * Sequential index next pseudonym claims.
     */
    private int $nextIndex = 0;

    /**
     * Cache mapping anonymised key → pseudonym ("user_a", "user_b", ...).
     *
     * @var array<string, string>
     */
    private array $assignments = [];

    /**
     * Manifest entries: pseudonym → hint label (no real identifier).
     *
     * @var array<string, string>
     */
    private array $manifestRows = [];

    public function __construct(
        public readonly User $subject,
        public readonly DateTimeImmutable $generatedAt,
    ) {}

    /**
     * Return a deterministic pseudonym for "another user". Subjects
     * other than the export's subject are mapped to user_a, user_b, …
     * in order of first encounter.
     *
     * Passing the subject (by id) returns "subject" — the export subject
     * is never pseudonymised.
     */
    public function obfuscateUser(User|int|null $other, ?string $hint = null): string
    {
        if ($other === null) {
            return 'unknown_user';
        }

        $id = $other instanceof User ? $other->id : $other;

        if ($id === $this->subject->id) {
            return 'subject';
        }

        $key = 'user:'.$id;

        if (! isset($this->assignments[$key])) {
            $pseudonym = $this->mintPseudonym('user_');
            $this->assignments[$key] = $pseudonym;
            $this->manifestRows[$pseudonym] = $hint ?? 'another user';
        }

        return $this->assignments[$key];
    }

    /**
     * Generic obfuscation for any other identifier (team id, sponsor id,
     * etc.) not tied to a User.
     */
    public function obfuscate(string $namespace, int|string $id, ?string $hint = null): string
    {
        $key = $namespace.':'.$id;

        if (! isset($this->assignments[$key])) {
            $pseudonym = $this->mintPseudonym($namespace.'_');
            $this->assignments[$key] = $pseudonym;
            $this->manifestRows[$pseudonym] = $hint ?? $namespace;
        }

        return $this->assignments[$key];
    }

    /**
     * @return array<string, string>
     */
    public function pseudonymTable(): array
    {
        return $this->manifestRows;
    }

    private function mintPseudonym(string $prefix): string
    {
        $index = $this->nextIndex++;

        $letters = '';
        do {
            $letters = chr(ord('a') + ($index % 26)).$letters;
            $index = intdiv($index, 26) - 1;
        } while ($index >= 0);

        return $prefix.$letters;
    }
}
