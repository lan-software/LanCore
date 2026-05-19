<?php

namespace App\Domain\Chat\Http\Controllers;

use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Services\PolicyResolver;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeamMember;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @see docs/mil-std-498/SRS.md CHT-F-018
 * @see docs/mil-std-498/IDD.md §3.14
 */
class ChatMentionSearchController extends Controller
{
    private const MAX_RESULTS = 8;

    public function __construct(
        private readonly PolicyResolver $policyResolver,
    ) {}

    public function __invoke(Request $request, ChatRoom $room): JsonResponse
    {
        $user = $request->user();
        $policy = $this->policyResolver->resolve($room);

        if (! $policy->canView($user, $room)) {
            throw new AuthorizationException;
        }

        $q = mb_strtolower(trim((string) $request->query('q', '')));

        $candidateIds = $this->candidateUserIds($room);

        if ($candidateIds === []) {
            return response()->json(['users' => []]);
        }

        $users = User::query()
            ->whereIn('id', $candidateIds)
            ->whereNotNull('username')
            ->when($q !== '', function (Builder $builder) use ($q): void {
                $needle = '%'.$q.'%';
                $builder->where(function (Builder $b) use ($needle): void {
                    $b->whereRaw('LOWER(username) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(name) LIKE ?', [$needle]);
                });
            })
            ->orderBy('username')
            ->limit(self::MAX_RESULTS)
            ->get(['id', 'name', 'username']);

        return response()->json([
            'users' => $users
                ->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'username' => $u->username,
                ])
                ->all(),
        ]);
    }

    /**
     * Union of (a) current room memberships and (b) the wider pool of users
     * who can be mentioned in this room — for a competition room that's all
     * team members of the competition, since auto-join may lag behind team
     * roster changes.
     *
     * @return array<int, string>
     */
    private function candidateUserIds(ChatRoom $room): array
    {
        $ids = $room->memberships()->pluck('user_id')->map(fn ($id) => (string) $id)->all();

        $competitionId = $this->competitionIdFromKey($room->key);

        if ($competitionId !== null) {
            $competition = Competition::query()->find($competitionId, ['id']);

            if ($competition !== null) {
                $teamMemberIds = CompetitionTeamMember::query()
                    ->whereNull('left_at')
                    ->whereHas('team', fn ($q) => $q->where('competition_id', $competitionId))
                    ->pluck('user_id')
                    ->map(fn ($id) => (string) $id)
                    ->all();

                $ids = array_unique(array_merge($ids, $teamMemberIds));
            }
        }

        return array_values(array_unique($ids));
    }

    private function competitionIdFromKey(string $key): ?string
    {
        if (preg_match('/^competition:([^:]+)(?::|$)/', $key, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
