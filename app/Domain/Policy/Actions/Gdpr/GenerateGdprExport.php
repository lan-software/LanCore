<?php

namespace App\Domain\Policy\Actions\Gdpr;

use App\Domain\Policy\Gdpr\GdprBinaryAttachment;
use App\Domain\Policy\Gdpr\GdprDataSourceRegistry;
use App\Domain\Policy\Gdpr\GdprExportContext;
use App\Domain\Policy\Gdpr\GdprExportResult;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Orchestrates the GDPR Article 15 export. Iterates the registered
 * GdprDataSources, writes JSON + binary attachments to a tmp dir,
 * zips into `storage/app/gdpr-exports/{user-id}-{Y-m-d_His}.zip`.
 *
 * If $password is non-null, every entry in the ZIP is AES-256
 * encrypted via PHP's ZipArchive::setEncryptionName().
 *
 * @see docs/mil-std-498/SSS.md CAP-GDPR-001..004
 * @see docs/mil-std-498/SDD.md "GDPR Export Implementation"
 */
class GenerateGdprExport
{
    public function __construct(private readonly GdprDataSourceRegistry $registry) {}

    public function execute(
        User $user,
        ?string $password = null,
        bool $includeSoftDeleted = false,
        ?string $outputDir = null,
    ): GdprExportResult {
        $generatedAt = new DateTimeImmutable;
        $context = new GdprExportContext($user, $generatedAt);

        $baseDir = $outputDir ?? storage_path('app/gdpr-exports');
        if (! is_dir($baseDir) && ! mkdir($baseDir, 0755, true) && ! is_dir($baseDir)) {
            throw new RuntimeException("Cannot create GDPR export directory: {$baseDir}");
        }

        $tmpDir = $baseDir.'/.tmp/'.Str::uuid()->toString();
        if (! mkdir($tmpDir, 0755, true) && ! is_dir($tmpDir)) {
            throw new RuntimeException("Cannot create tmp dir: {$tmpDir}");
        }

        $sourceManifest = [];

        foreach ($this->registry->all() as $key => $source) {
            $result = $source->for($user, $context);

            file_put_contents(
                $tmpDir.'/'.$key.'.json',
                json_encode($result->records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            );

            if ($result->files !== []) {
                $sourceFileDir = $tmpDir.'/'.$key;
                if (! is_dir($sourceFileDir)) {
                    mkdir($sourceFileDir, 0755, true);
                }

                foreach ($result->files as $file) {
                    if (! $file instanceof GdprBinaryAttachment || ! is_file($file->absoluteSourcePath)) {
                        continue;
                    }

                    copy($file->absoluteSourcePath, $sourceFileDir.'/'.$file->filename);
                }
            }

            $sourceManifest[] = [
                'key' => $source->key(),
                'label' => $source->label(),
                'record_count' => count($result->records),
                'binary_attachments' => count($result->files),
            ];
        }

        $manifest = [
            'subject' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
            ],
            'generated_at' => $generatedAt->format(DATE_ATOM),
            'app_version' => (string) config('app.version', 'unknown'),
            'include_soft_deleted' => $includeSoftDeleted,
            'sources' => $sourceManifest,
            'pseudonyms' => $context->pseudonymTable(),
        ];

        file_put_contents(
            $tmpDir.'/manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );

        file_put_contents($tmpDir.'/README.txt', $this->readme());

        $zipPath = sprintf(
            '%s/%d-%s.zip',
            rtrim($baseDir, '/'),
            $user->id,
            $generatedAt->format('Y-m-d_His'),
        );

        $this->buildZip($tmpDir, $zipPath, $password);

        $this->rrmdir($tmpDir);

        $size = (int) (filesize($zipPath) ?: 0);

        return new GdprExportResult($zipPath, $size, $manifest);
    }

    private function buildZip(string $sourceDir, string $zipPath, ?string $password): void
    {
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new RuntimeException("Cannot create ZIP at {$zipPath}");
        }

        if ($password !== null) {
            $zip->setPassword($password);
        }

        $files = $this->walk($sourceDir);

        foreach ($files as $absolute) {
            $relative = ltrim(substr($absolute, strlen($sourceDir)), '/');
            $zip->addFile($absolute, $relative);

            if ($password !== null) {
                $zip->setEncryptionName($relative, ZipArchive::EM_AES_256, $password);
            }
        }

        $zip->close();
    }

    /**
     * @return list<string>
     */
    private function walk(string $dir): array
    {
        $out = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $out[] = $file->getPathname();
            }
        }

        sort($out);

        return $out;
    }

    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir.'/'.$entry;
            is_dir($path) ? $this->rrmdir($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    private function readme(): string
    {
        return <<<'TXT'
GDPR Article 15 — Subject Access Request export
===============================================

EN: This archive contains every record we hold about you in this
    application, in JSON format. Where applicable, identifiers of
    other users have been replaced with deterministic pseudonyms
    ("user_a", "user_b", …) so that no third-party data is
    disclosed. The pseudonym table is in `manifest.json` (no
    reverse mapping is included or retained).

DE: Dieses Archiv enthält sämtliche Daten, die in dieser
    Anwendung über Sie gespeichert sind, im JSON-Format. Wo
    relevant, wurden Kennungen anderer Personen durch
    deterministische Pseudonyme ersetzt ("user_a", "user_b", …),
    sodass keine Daten Dritter offengelegt werden. Die
    Pseudonym-Tabelle befindet sich in `manifest.json`. Die
    Rückzuordnung ist weder enthalten noch wird sie gespeichert.

Files:
  manifest.json               Export metadata, source list, pseudonym table
  profile.json                User account data
  policy_acceptances.json     Policy acceptances and withdrawals
  sessions.json               Web session metadata
  audits.json                 Audit-log entries involving the subject
  shop.json                   Orders, carts, checkout-condition acks
  ticketing.json              Tickets and seat assignments
  competitions.json           Team memberships, invites, join requests, proofs
  news.json                   Comments and votes
  notifications.json          Preferences and push subscriptions
  orga_team.json              Organisation team memberships
  sponsoring.json             Sponsor representative associations
  achievements.json           Earned achievements
  policy_acceptances/*.pdf    PDF copies of accepted policy versions
TXT;
    }
}
