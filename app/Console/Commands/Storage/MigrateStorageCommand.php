<?php

namespace App\Console\Commands\Storage;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

#[Signature('storage:migrate {--from=local : Source disk (local or s3)} {--to=s3 : Destination disk (local or s3)} {--path= : Only migrate files under this path prefix} {--delete : Delete files from source after successful copy} {--dry-run : Preview files that would be migrated without actually migrating}')]
#[Description('Migrate storage files between disks (e.g. local to s3 or s3 to local)')]
class MigrateStorageCommand extends Command
{
    private const VALID_DISKS = ['local', 's3'];

    public function handle(): int
    {
        $from = $this->option('from');
        $to = $this->option('to');
        $pathPrefix = $this->option('path') ?? '';
        $delete = $this->option('delete');
        $dryRun = $this->option('dry-run');

        if (! $this->validateOptions($from, $to)) {
            return self::FAILURE;
        }

        $label = "'{$from}' → '{$to}'" . ($pathPrefix ? " (path: {$pathPrefix})" : '');
        $this->info("Migrating storage {$label}...");

        if ($dryRun) {
            $this->warn('Running in dry-run mode — no files will be copied or deleted.');
        }

        $sourceDisk = Storage::disk($from);
        $destinationDisk = Storage::disk($to);

        /** @var array<int, string> $files */
        $files = $sourceDisk->allFiles($pathPrefix ?: null);

        if (empty($files)) {
            $this->info('No files found on source disk.');

            return self::SUCCESS;
        }

        $total = count($files);
        $this->info("Found {$total} file(s) to migrate.");
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

    private function validateOptions(mixed $from, mixed $to): bool
    {
        if (! in_array($from, self::VALID_DISKS, strict: true)) {
            $this->error("Invalid source disk '{$from}'. Valid options: " . implode(', ', self::VALID_DISKS) . '.');

            return false;
        }

        if (! in_array($to, self::VALID_DISKS, strict: true)) {
            $this->error("Invalid destination disk '{$to}'. Valid options: " . implode(', ', self::VALID_DISKS) . '.');

            return false;
        }

        if ($from === $to) {
            $this->error("Source and destination disks must be different (both are '{$from}').");

            return false;
        }

        return true;
    }
}
