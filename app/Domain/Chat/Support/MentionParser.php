<?php

namespace App\Domain\Chat\Support;

use App\Models\User;

/**
 * Parses `@username` mentions out of a chat message body. Returns the resolved
 * user ids. Case-insensitive, deduplicated, ignores leading/trailing punctuation.
 *
 * @see docs/mil-std-498/SRS.md CHT-F-013
 */
class MentionParser
{
    /**
     * Matches `@` followed by 3-32 alphanumeric/underscore characters, anchored
     * to a word boundary on the left so emails (`foo@bar.com`) aren't parsed.
     */
    private const PATTERN = '/(?<=^|\s)@([A-Za-z0-9_]{3,32})/u';

    /**
     * @return array<int, int>
     */
    public function parse(string $body): array
    {
        if (! preg_match_all(self::PATTERN, $body, $matches)) {
            return [];
        }

        $usernames = collect($matches[1])
            ->map(fn (string $u) => strtolower($u))
            ->unique()
            ->values()
            ->all();

        if ($usernames === []) {
            return [];
        }

        return User::query()
            ->whereIn(\DB::raw('LOWER(username)'), $usernames)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
