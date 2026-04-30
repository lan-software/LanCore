<?php

namespace App\Console\Commands\Gdpr;

use App\Domain\DataLifecycle\Anonymizers\UserAnonymizer;
use App\Domain\DataLifecycle\Services\EmailHasher;
use App\Domain\Policy\Actions\Gdpr\GenerateGdprExport;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\password as promptPassword;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

#[Signature('gdpr:export-user {email?} {--password=} {--include-soft-deleted} {--output-dir=}')]
#[Description('Generate a GDPR Article 15 export of every record held about a user, written to storage/app/gdpr-exports/. Optionally AES-256 password-protected.')]
class ExportUserDataCommand extends Command
{
    public function handle(GenerateGdprExport $action): int
    {
        intro('GDPR Article 15 — Subject Access Request');

        warning(
            'This command exports every record held about a single user. The resulting ZIP is sensitive personal data; '
            .'handle and transmit accordingly.',
        );

        $email = (string) ($this->argument('email') ?? text(
            label: 'Email address of the user to export',
            placeholder: 'user@example.com',
            required: true,
        ));

        $includeSoftDeleted = (bool) $this->option('include-soft-deleted');

        $user = $this->locateUser($email, $includeSoftDeleted);

        if ($user === null) {
            error("No user found for {$email}.");

            return self::FAILURE;
        }

        if ($user->isAnonymized()) {
            warning(
                "User #{$user->id} has been anonymized. The export will contain only data retained after deletion "
                .'(typically accounting, audit, and consent records).',
            );
        }

        info(sprintf('Found user #%d: %s <%s>', $user->id, $user->name ?? '(unnamed)', $user->email));
        $outputDir = $this->option('output-dir') ?: null;

        $password = $this->option('password');
        if ($password === null && ! $this->option('no-interaction')) {
            $shouldEncrypt = confirm(
                label: 'Password-protect the ZIP (AES-256)?',
                default: true,
            );

            if ($shouldEncrypt) {
                $password = promptPassword(
                    label: 'Password for the ZIP archive',
                    required: true,
                );
            }
        }

        if (! confirm(label: 'Proceed with export?', default: true)) {
            note('Aborted by operator.');

            return self::SUCCESS;
        }

        try {
            $result = spin(
                callback: fn () => $action->execute($user, $password ?: null, $includeSoftDeleted, $outputDir),
                message: 'Generating export…',
            );
        } catch (Throwable $e) {
            error('Export failed: '.$e->getMessage());

            return self::FAILURE;
        }

        info('Export written to '.$result->absoluteZipPath);
        info(sprintf('Size: %s bytes', number_format($result->byteSize)));

        $rows = array_map(
            fn (array $s) => [$s['key'], $s['label'], (string) $s['record_count'], (string) $s['binary_attachments']],
            $result->manifest['sources'],
        );

        table(
            headers: ['Key', 'Label', 'Records', 'Binary files'],
            rows: $rows,
        );

        return self::SUCCESS;
    }

    /**
     * Locate a user by their original email address. Falls back to the
     * salted email_hash so that post-deletion exports still work even after
     * the {@see UserAnonymizer} has
     * replaced the email column.
     */
    private function locateUser(string $email, bool $includeSoftDeleted): ?User
    {
        $query = User::query();
        if ($includeSoftDeleted) {
            $query->withTrashed();
        }

        $user = (clone $query)->where('email', $email)->first();
        if ($user !== null) {
            return $user;
        }

        $hash = app(EmailHasher::class)->hash($email);

        return $query->where('email_hash', $hash)->first();
    }
}
