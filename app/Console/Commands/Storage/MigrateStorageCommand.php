<?php

namespace App\Console\Commands\Storage;

use App\Support\StorageRole;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

#[Signature('storage:migrate {--from= : Source disk (local, public, s3, s3_public, s3_private)} {--to= : Destination disk (local, public, s3, s3_public, s3_private)} {--path= : Only migrate files under this path prefix} {--delete : Delete files from source after successful copy} {--dry-run : Preview files that would be migrated without actually migrating} {--skip-reachability : Skip the read/write probe of source and destination before copying}')]
#[Description('Migrate storage files between disks (e.g. local→s3_private or s3→s3_public). Runs an interactive wizard with a reachability probe when invoked without --from/--to.')]
class MigrateStorageCommand extends Command
{
    private const VALID_DISKS = ['local', 'public', 's3', 's3_public', 's3_private'];

    public function handle(): int
    {
        $from = $this->option('from');
        $to = $this->option('to');
        $pathPrefix = (string) ($this->option('path') ?? '');
        $delete = (bool) $this->option('delete');
        $dryRun = (bool) $this->option('dry-run');
        $skipReachability = (bool) $this->option('skip-reachability');

        $interactive = $from === null && $to === null && ! $this->option('no-interaction');

        if ($interactive) {
            intro('LanCore storage migration wizard');
            $this->printRoleSummary();

            [$from, $to, $pathPrefix, $delete, $dryRun, $skipReachability] =
                $this->collectAnswersInteractively();
        }

        if (! $this->validateOptions($from, $to)) {
            return self::FAILURE;
        }

        if (! $skipReachability) {
            if (! $this->reachabilityCheck((string) $from, (string) $to)) {
                $this->error('Reachability check failed — aborting before any files are touched.');

                return self::FAILURE;
            }
        }

        $label = "'{$from}' → '{$to}'".($pathPrefix !== '' ? " (path: {$pathPrefix})" : '');
        $this->info("Migrating storage {$label}...");

        if ($dryRun) {
            $this->warn('Running in dry-run mode — no files will be copied or deleted.');
        }

        $sourceDisk = Storage::disk((string) $from);
        $destinationDisk = Storage::disk((string) $to);

        /** @var array<int, string> $files */
        $files = $sourceDisk->allFiles($pathPrefix !== '' ? $pathPrefix : null);

        if (empty($files)) {
            $this->info('No files found on source disk.');

            return self::SUCCESS;
        }

        $total = count($files);
        $this->info("Found {$total} file(s) to migrate.");

        if ($interactive && ! confirm("Proceed with copying {$total} file(s) from '{$from}' to '{$to}'?", default: true)) {
            warning('Aborted by user.');

            return self::SUCCESS;
        }

        $this->newLine();

        $migrated = 0;
        $failed = 0;
        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        foreach ($files as $file) {
            try {
                if (! $dryRun) {
                    $stream = $sourceDisk->readStream($file);

                    if (! is_resource($stream)) {
                        throw new RuntimeException("Could not open stream for: {$file}");
                    }

                    $destinationDisk->writeStream($file, $stream);

                    fclose($stream);

                    if ($delete) {
                        $sourceDisk->delete($file);
                    }
                }

                $migrated++;
            } catch (Throwable $e) {
                $failed++;
                $progressBar->clear();
                $this->error("Failed '{$file}': {$e->getMessage()}");
                $progressBar->display();
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        if ($dryRun) {
            $this->info("Dry run complete — {$migrated} file(s) would be migrated:");
            foreach ($files as $file) {
                $this->line("  {$file}");
            }

            return self::SUCCESS;
        }

        $this->info("Migration complete — migrated: {$migrated}, failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: bool, 4: bool, 5: bool}
     */
    private function collectAnswersInteractively(): array
    {
        $diskOptions = $this->buildDiskOptions();

        $from = (string) select(
            label: 'Source disk (read from)',
            options: $diskOptions,
            default: 'local',
            hint: 'Files will be read from this disk.',
        );

        $destinationOptions = $diskOptions;
        unset($destinationOptions[$from]);

        $to = (string) select(
            label: 'Destination disk (write to)',
            options: $destinationOptions,
            default: $this->suggestDestination($from, array_keys($destinationOptions)),
            hint: 'Files will be written here. Choose s3_public for assets and s3_private for sensitive artifacts.',
        );

        $this->warnIfRoleMismatch($to);

        $pathPrefix = (string) text(
            label: 'Limit to a path prefix?',
            placeholder: 'e.g. avatars/  (leave empty for everything)',
            default: '',
            hint: 'Only files under this prefix will be migrated.',
        );

        $dryRun = confirm(
            label: 'Dry run? (preview without copying)',
            default: true,
            hint: 'Strongly recommended for the first pass.',
        );

        $delete = false;

        if (! $dryRun) {
            $delete = confirm(
                label: "Delete from source ('{$from}') after a successful copy?",
                default: false,
                hint: 'Leave disabled to keep a fallback until you have verified the destination.',
            );
        }

        $skipReachability = ! confirm(
            label: 'Run a read/write reachability probe on both disks before starting?',
            default: true,
            hint: 'Catches missing credentials or wrong endpoints before any data is touched.',
        );

        return [$from, $to, $pathPrefix, $delete, $dryRun, $skipReachability];
    }

    /**
     * @return array<string, string>
     */
    private function buildDiskOptions(): array
    {
        $options = [];

        foreach (self::VALID_DISKS as $disk) {
            $options[$disk] = $this->describeDisk($disk);
        }

        return $options;
    }

    private function describeDisk(string $disk): string
    {
        $driver = (string) config("filesystems.disks.{$disk}.driver", '?');

        if ($driver === 's3') {
            $bucket = (string) (config("filesystems.disks.{$disk}.bucket") ?? '(unset)');
            $endpoint = (string) (config("filesystems.disks.{$disk}.endpoint") ?? '(default)');

            return "{$disk}  ·  s3://{$bucket} @ {$endpoint}";
        }

        if ($driver === 'local') {
            $root = (string) (config("filesystems.disks.{$disk}.root") ?? '(unknown)');

            return "{$disk}  ·  local: {$root}";
        }

        return "{$disk}  ·  {$driver}";
    }

    /**
     * @param  array<int, string>  $available
     */
    private function suggestDestination(string $from, array $available): string
    {
        $suggestion = match ($from) {
            'local' => 's3_private',
            'public' => 's3_public',
            's3' => 's3_public',
            's3_public', 's3_private' => 'local',
            default => $available[0] ?? 's3_public',
        };

        return in_array($suggestion, $available, strict: true)
            ? $suggestion
            : ($available[0] ?? 's3_public');
    }

    private function warnIfRoleMismatch(string $destination): void
    {
        $configuredPublic = StorageRole::publicDiskName();
        $configuredPrivate = StorageRole::privateDiskName();

        if ($destination === 's3_private' && $configuredPrivate !== 's3_private') {
            warning("Heads up: FILESYSTEM_PRIVATE_DISK is currently '{$configuredPrivate}'. Migrated files will land in 's3_private', but the app still reads private files from '{$configuredPrivate}' until you switch the env var.");

            return;
        }

        if ($destination === 's3_public' && $configuredPublic !== 's3_public') {
            warning("Heads up: FILESYSTEM_PUBLIC_DISK is currently '{$configuredPublic}'. Migrated files will land in 's3_public', but the app still serves public files from '{$configuredPublic}' until you switch the env var.");
        }
    }

    private function printRoleSummary(): void
    {
        $rows = [
            ['public_disk (FILESYSTEM_PUBLIC_DISK)', StorageRole::publicDiskName()],
            ['private_disk (FILESYSTEM_PRIVATE_DISK)', StorageRole::privateDiskName()],
            ['default disk (FILESYSTEM_DISK)', (string) config('filesystems.default')],
        ];

        table(headers: ['Semantic role', 'Currently mapped to'], rows: $rows);
    }

    private function reachabilityCheck(string $from, string $to): bool
    {
        info('Running reachability probe…');

        $sourceOk = $this->probeDisk($from, requireWrite: false);
        $destOk = $this->probeDisk($to, requireWrite: true);

        return $sourceOk && $destOk;
    }

    private function probeDisk(string $disk, bool $requireWrite): bool
    {
        try {
            /** @var Filesystem $filesystem */
            $filesystem = spin(
                callback: fn (): Filesystem => Storage::disk($disk),
                message: "Resolving disk '{$disk}'…",
            );
        } catch (Throwable $e) {
            $this->error("  ✗ {$disk}: could not resolve — {$e->getMessage()}");

            return false;
        }

        try {
            spin(
                callback: fn () => $filesystem->files(),
                message: "Listing '{$disk}'…",
            );
        } catch (Throwable $e) {
            $this->error("  ✗ {$disk}: list failed — {$e->getMessage()}");

            return false;
        }

        if (! $requireWrite) {
            $this->line("  <fg=green>✓</> {$disk}: reachable (read)");

            return true;
        }

        $key = sprintf('_lancore-probe/migrate-%s-%s.txt', now()->format('Ymd-His'), Str::random(6));
        $payload = "probe {$disk} ".now()->toIso8601String();

        try {
            spin(
                callback: function () use ($filesystem, $key, $payload): void {
                    if ($filesystem->put($key, $payload) !== true) {
                        throw new RuntimeException('put() returned false');
                    }
                    if ($filesystem->get($key) !== $payload) {
                        throw new RuntimeException('readback mismatch');
                    }
                    $filesystem->delete($key);
                },
                message: "Probing write/read/delete on '{$disk}'…",
            );
        } catch (Throwable $e) {
            $this->error("  ✗ {$disk}: write probe failed — {$e->getMessage()}");
            $this->cleanupProbe($filesystem, $key);

            return false;
        }

        $this->line("  <fg=green>✓</> {$disk}: reachable (read + write)");

        return true;
    }

    private function cleanupProbe(Filesystem $filesystem, string $key): void
    {
        try {
            $filesystem->delete($key);
        } catch (Throwable) {
            // Ignore — failing to clean up the probe should not mask the original error.
        }
    }

    private function validateOptions(mixed $from, mixed $to): bool
    {
        if (! in_array($from, self::VALID_DISKS, strict: true)) {
            $this->error("Invalid source disk '{$from}'. Valid options: ".implode(', ', self::VALID_DISKS).'.');

            return false;
        }

        if (! in_array($to, self::VALID_DISKS, strict: true)) {
            $this->error("Invalid destination disk '{$to}'. Valid options: ".implode(', ', self::VALID_DISKS).'.');

            return false;
        }

        if ($from === $to) {
            $this->error("Source and destination disks must be different (both are '{$from}').");

            return false;
        }

        if ($from === 's3' && $to === 's3') {
            note('Tip: the legacy mono-bucket "s3" disk is kept for backwards compatibility. New deployments should use s3_public for assets and s3_private for sensitive artifacts.');
        }

        return true;
    }
}
