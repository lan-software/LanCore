<?php

namespace App\Console\Commands\Competition;

use App\Domain\Competition\Actions\CreateTeam;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeam;
use App\Domain\Competition\Models\CompetitionTeamMember;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

/**
 * Create a team for a competition from the console.
 *
 * Examples:
 *   php artisan competitions:create-team 2 17 --name="Blasenmacher" --tag=BLAS
 *   php artisan competitions:create-team 2 captain@example.com --name="Gewinnertyp"
 *   php artisan competitions:create-team 2 markus --name="Force Team" --force
 *
 * @see docs/mil-std-498/SRS.md COMP-F-005
 */
class CreateTeamCommand extends Command
{
    protected $signature = 'competitions:create-team
        {competition : Competition id.}
        {captain : Captain user id, email, or username.}
        {--name= : Team name (required).}
        {--tag= : Optional team tag.}
        {--force : Bypass signup-rule enforcement.}';

    protected $description = 'Create a team under a competition and assign a captain.';

    public function handle(CreateTeam $createTeam): int
    {
        $competition = Competition::find((int) $this->argument('competition'));
        if ($competition === null) {
            $this->error("Competition #{$this->argument('competition')} not found.");

            return self::FAILURE;
        }

        $captain = $this->resolveUser((string) $this->argument('captain'));
        if ($captain === null) {
            $this->error("Captain '{$this->argument('captain')}' not found (tried id, email, username).");

            return self::FAILURE;
        }

        $name = (string) $this->option('name');
        if ($name === '') {
            $this->error('--name is required.');

            return self::FAILURE;
        }

        $tag = $this->option('tag') !== null ? (string) $this->option('tag') : null;

        try {
            if ($this->option('force')) {
                $team = $this->createForced($competition, $captain, $name, $tag);
            } else {
                $team = $createTeam->execute($competition, $captain, [
                    'name' => $name,
                    'tag' => $tag,
                ]);
            }
        } catch (ValidationException $e) {
            $this->error('Signup rules rejected the team creation:');
            foreach ($e->errors() as $field => $messages) {
                foreach ((array) $messages as $message) {
                    $this->line("  - [{$field}] {$message}");
                }
            }
            $this->line('Re-run with --force to bypass.');

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Team #%d "%s"%s created under competition #%d (captain: #%d %s).',
            $team->id,
            $team->name,
            $team->tag ? " [{$team->tag}]" : '',
            $competition->id,
            $captain->id,
            $captain->name ?? $captain->username ?? $captain->email,
        ));

        return self::SUCCESS;
    }

    private function resolveUser(string $identifier): ?User
    {
        if (ctype_digit($identifier)) {
            $user = User::find((int) $identifier);
            if ($user !== null) {
                return $user;
            }
        }

        return User::where('email', $identifier)->orWhere('username', $identifier)->first();
    }

    private function createForced(Competition $competition, User $captain, string $name, ?string $tag): CompetitionTeam
    {
        $team = CompetitionTeam::create([
            'competition_id' => $competition->id,
            'name' => $name,
            'tag' => $tag,
            'captain_user_id' => $captain->id,
        ]);

        CompetitionTeamMember::create([
            'team_id' => $team->id,
            'user_id' => $captain->id,
            'joined_at' => now(),
        ]);

        return $team->load('captain', 'activeMembers.user');
    }
}
