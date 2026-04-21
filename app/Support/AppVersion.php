<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class AppVersion
{
    /**
     * @return array{version: string, commit: ?string, builtAt: ?string, phpVersion: string, laravelVersion: string}
     */
    public static function summary(): array
    {
        return Cache::remember('app.version.summary', 3600, function (): array {
            return [
                'version' => self::version(),
                'commit' => self::commit(),
                'builtAt' => self::builtAt(),
                'phpVersion' => PHP_VERSION,
                'laravelVersion' => app()->version(),
            ];
        });
    }

    public static function version(): string
    {
        $envVersion = config('app.version');
        if (is_string($envVersion) && $envVersion !== '') {
            return $envVersion;
        }

        $composerPath = base_path('composer.json');
        if (File::exists($composerPath)) {
            $composer = json_decode(File::get($composerPath), true);
            if (is_array($composer) && isset($composer['version']) && is_string($composer['version'])) {
                return $composer['version'];
            }
        }

        return '0.0.0-dev';
    }

    public static function commit(): ?string
    {
        $envCommit = config('app.commit');
        if (is_string($envCommit) && $envCommit !== '') {
            return substr($envCommit, 0, 12);
        }

        $head = base_path('.git/HEAD');
        if (! File::exists($head)) {
            return null;
        }

        $headContent = trim(File::get($head));
        if (str_starts_with($headContent, 'ref: ')) {
            $refPath = base_path('.git/'.substr($headContent, 5));
            if (File::exists($refPath)) {
                return substr(trim(File::get($refPath)), 0, 12);
            }

            return null;
        }

        return substr($headContent, 0, 12);
    }

    public static function builtAt(): ?string
    {
        $built = config('app.built_at');

        return is_string($built) && $built !== '' ? $built : null;
    }
}
