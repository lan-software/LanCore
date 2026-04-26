<?php

namespace App\Console\Commands\Storage;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

#[Signature('storage:test-s3 {--disk= : Test only this disk (s3_public or s3_private). Tests both when omitted.} {--keep : Do not delete the probe file when finished}')]
#[Description('Probe the configured S3 disks (s3_public and s3_private) by performing write, read, metadata, URL, and delete round-trips.')]
class TestS3ConnectionCommand extends Command
{
    private const TESTABLE_DISKS = ['s3_public', 's3_private'];

    public function handle(): int
    {
        $only = $this->option('disk');

        if ($only !== null && ! in_array($only, self::TESTABLE_DISKS, strict: true)) {
            $this->error("Invalid disk '{$only}'. Valid options: ".implode(', ', self::TESTABLE_DISKS).'.');

            return self::FAILURE;
        }

        $disks = $only !== null ? [$only] : self::TESTABLE_DISKS;
        $hadFailure = false;

        foreach ($disks as $disk) {
            $this->newLine();
            $this->line("<options=bold,underscore>Testing disk: {$disk}</>");

            if (! $this->probeDisk($disk)) {
                $hadFailure = true;
            }
        }

        $this->newLine();

        if ($hadFailure) {
            $this->error('One or more disks failed.');

            return self::FAILURE;
        }

        $this->info('All disks responded successfully.');

        return self::SUCCESS;
    }

    private function probeDisk(string $disk): bool
    {
        $config = config("filesystems.disks.{$disk}");

        if (! is_array($config) || ($config['driver'] ?? null) !== 's3') {
            $this->error("  Disk '{$disk}' is not configured as an S3 driver.");

            return false;
        }

        $this->line(sprintf(
            '  bucket=%s region=%s endpoint=%s path-style=%s',
            $config['bucket'] ?? '(unset)',
            $config['region'] ?? '(unset)',
            $config['endpoint'] ?? '(default)',
            ($config['use_path_style_endpoint'] ?? false) ? 'true' : 'false',
        ));

        $key = sprintf(
            '_lancore-probe/%s-%s.txt',
            now()->format('Ymd-His'),
            Str::random(8),
        );
        $payload = "LanCore S3 probe written at {$disk} ".now()->toIso8601String();

        try {
            $filesystem = Storage::disk($disk);
        } catch (Throwable $e) {
            $this->error("  Could not resolve disk: {$e->getMessage()}");

            return false;
        }

        $ok = true;
        $ok = $this->step('write', fn () => $filesystem->put($key, $payload) === true ? null : 'put() returned false') && $ok;
        $ok = $this->step('exists', fn () => $filesystem->exists($key) ? null : 'exists() returned false') && $ok;
        $ok = $this->step('read', fn () => $this->verifyContents($filesystem, $key, $payload)) && $ok;
        $ok = $this->step('size', fn () => $this->verifySize($filesystem, $key, strlen($payload))) && $ok;
        $ok = $this->step('url', fn () => $this->verifyUrl($filesystem, $disk, $key)) && $ok;

        if ($this->option('keep')) {
            $this->warn("  Keeping probe object: {$key}");
        } else {
            $ok = $this->step('delete', fn () => $filesystem->delete($key) ? null : 'delete() returned false') && $ok;
        }

        return $ok;
    }

    /**
     * Run a single named probe step. The closure should return null on
     * success or a string error message describing the failure.
     */
    private function step(string $label, callable $closure): bool
    {
        $start = microtime(true);

        try {
            $error = $closure();
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        $ms = (int) round((microtime(true) - $start) * 1000);

        if ($error === null) {
            $this->line("  <fg=green>✓</> {$label} ({$ms} ms)");

            return true;
        }

        $this->line("  <fg=red>✗</> {$label} ({$ms} ms) — {$error}");

        return false;
    }

    private function verifyContents(Filesystem $filesystem, string $key, string $expected): ?string
    {
        $contents = $filesystem->get($key);

        if ($contents === null) {
            return 'get() returned null';
        }

        if ($contents !== $expected) {
            return 'contents do not match what was written';
        }

        return null;
    }

    private function verifySize(Filesystem $filesystem, string $key, int $expected): ?string
    {
        $size = $filesystem->size($key);

        if ($size !== $expected) {
            return "size mismatch (expected {$expected}, got {$size})";
        }

        return null;
    }

    private function verifyUrl(Filesystem $filesystem, string $disk, string $key): ?string
    {
        if ($disk === 's3_public') {
            $url = $filesystem->url($key);

            if (! is_string($url) || $url === '') {
                return 'url() returned an empty value';
            }

            $this->line("    public url: {$url}");

            return null;
        }

        try {
            $url = $filesystem->temporaryUrl($key, now()->addMinutes(5));
        } catch (Throwable $e) {
            return 'temporaryUrl() failed: '.$e->getMessage();
        }

        if (! is_string($url) || $url === '') {
            return 'temporaryUrl() returned an empty value';
        }

        $this->line('    presigned url: '.Str::limit($url, 120));

        return null;
    }
}
