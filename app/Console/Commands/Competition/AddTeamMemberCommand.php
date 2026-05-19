<?php

namespace App\Console\Commands\Competition;

use App\Domain\Competition\Actions\JoinTeam;
use App\Domain\Competition\Models\CompetitionTeam;
use App\Domain\Competition\Models\CompetitionTeamMember;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Add a user to a competition team from the console.
 *
 * Examples:
 *   php artisan competitions:add-team-member 4 17
 *   php artisan competitions:add-team-member 4 markus@example.com
 *   php artisan competitions:add-team-member 4 markus --force
 *
 * @see docs/mil-std-498/SRS.md COMP-F-006
 */
class AddTeamMemberCommand extends Command
{
    protected $signature = 'competitions:add-team-member
        {team : Competition team id.}
        {user : User id, email, or username.}
        {--force : Bypass signup-rule enforcement.}';

    protected $description = 'Add a user to a competition team.';

    public function handle(JoinTeam $joinTeam): int
    {
        $team = CompetitionTeam::with('competition')->find((string) $this->argument('team'));
        if ($team === null) {
            $this->error("Team #{$this->argument('team')} not found.");

            return self::FAILURE;
        }

        $user = $this->resolveUser((string) $this->argument('user'));
        if ($user === null) {
            $this->error("User '{$this->argument('user')}' not found (tried id, email, username).");

            return self::FAILURE;
        }

        $existing = CompetitionTeamMember::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->first();
        if ($existing !== null) {
            $this->warn(sprintf(
                'User #%d is already an active member of team #%d "%s" (membership #%d).',
                $user->id,
                $team->id,
                $team->name,
                $existing->id,
            ));

            return self::SUCCESS;
        }

        try {
            if ($this->option('force')) {
                $member = CompetitionTeamMember::create([
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                    'joined_at' => now(),
                ]);
            } else {
                $member = $joinTeam->execute($team, $user);
            }
        } catch (ValidationException $e) {
            $this->error('Signup rules rejected the join:');
            foreach ($e->errors() as $field => $messages) {
                foreach ((array) $messages as $message) {
                    $this->line("  - [{$field}] {$message}");
                }
            }
            $this->line('Re-run with --force to bypass.');

            return self::FAILURE;
        }

        $this->info(sprintf(
            'User #%d %s joined team #%d "%s" in competition #%d "%s" (membership #%d).',
            $user->id,
            $user->name ?? $user->username ?? $user->email,
            $team->id,
            $team->name,
            $team->competition->id,
            $team->competition->name,
            $member->id,
        ));

        return self::SUCCESS;
    }

    private function resolveUser(string $identifier): ?User
    {
        if (Str::isUlid($identifier)) {
            $user = User::find($identifier);
            if ($user !== null) {
                return $user;
            }
        }

        return User::where('email', $identifier)->orWhere('username', $identifier)->first();
    }
}
