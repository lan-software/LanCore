<?php

namespace App\Domain\Policy\Gdpr;

use App\Domain\Policy\Gdpr\Contracts\GdprDataSource;
use App\Domain\Policy\Models\PolicyAcceptance;
use App\Models\User;
use App\Support\StorageRole;

class PolicyDataSource implements GdprDataSource
{
    public function key(): string
    {
        return 'policy_acceptances';
    }

    public function label(): string
    {
        return 'Policy acceptances and withdrawals';
    }

    public function for(User $user, GdprExportContext $context): GdprDataSourceResult
    {
        $acceptances = PolicyAcceptance::query()
            ->where('user_id', $user->id)
            ->with('version.policy')
            ->orderBy('accepted_at')
            ->get();

        $records = $acceptances->map(fn (PolicyAcceptance $a) => [
            'id' => $a->id,
            'policy_key' => $a->version?->policy?->key,
            'policy_name' => $a->version?->policy?->name,
            'version_number' => $a->version?->version_number,
            'locale' => $a->locale,
            'accepted_at' => $a->accepted_at?->toIso8601String(),
            'source' => $a->source?->value,
            'ip_address' => $a->ip_address,
            'user_agent' => $a->user_agent,
            'withdrawn_at' => $a->withdrawn_at?->toIso8601String(),
            'withdrawn_reason' => $a->withdrawn_reason,
            'withdrawn_ip' => $a->withdrawn_ip,
            'withdrawn_user_agent' => $a->withdrawn_user_agent,
        ])->all();

        $files = [];
        $disk = StorageRole::private();
        $diskRoot = config('filesystems.disks.'.StorageRole::privateDiskName().'.root');

        foreach ($acceptances as $a) {
            $path = $a->version?->pdf_path;

            if ($path && $disk->exists($path) && is_string($diskRoot)) {
                $absolute = rtrim($diskRoot, '/').'/'.ltrim($path, '/');

                if (is_file($absolute)) {
                    $filename = sprintf(
                        '%s-v%d.pdf',
                        $a->version->policy?->key ?? 'policy',
                        $a->version->version_number,
                    );
                    $files[] = new GdprBinaryAttachment($filename, $absolute);
                }
            }
        }

        return new GdprDataSourceResult(['acceptances' => $records], $files);
    }
}
