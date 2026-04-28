<?php

namespace App\Domain\Policy\Actions;

use App\Domain\Policy\Events\PolicyVersionPublished;
use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyVersion;
use App\Models\User;
use App\Support\StorageRole;
use Barryvdh\DomPDF\Facade\Pdf;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

/**
 * Publish a new version of a policy.
 *
 * Locks the parent Policy row, computes the next per-(policy, locale) version
 * number, renders + stores a PDF snapshot, and updates the gating pointer ONLY
 * when the publish is flagged non-editorial. Editorial publishes are silent —
 * they bump version_number for audit history but do not force re-acceptance.
 *
 * @see docs/mil-std-498/SSS.md CAP-POL-003
 * @see docs/mil-std-498/SRS.md POL-F-008, POL-F-009, POL-F-016
 */
class PublishPolicyVersion
{
    public function execute(
        Policy $policy,
        string $content,
        bool $isNonEditorial,
        ?string $publicStatement,
        User $publishedBy,
        ?string $locale = null,
        ?DateTimeInterface $effectiveAt = null,
    ): PolicyVersion {
        $locale ??= (string) config('app.locale');
        $effectiveAt ??= now();

        return DB::transaction(function () use ($policy, $content, $isNonEditorial, $publicStatement, $publishedBy, $locale, $effectiveAt): PolicyVersion {
            $lockedPolicy = Policy::query()->whereKey($policy->id)->lockForUpdate()->firstOrFail();

            $nextVersionNumber = (int) PolicyVersion::query()
                ->where('policy_id', $lockedPolicy->id)
                ->where('locale', $locale)
                ->max('version_number') + 1;

            $version = PolicyVersion::create([
                'policy_id' => $lockedPolicy->id,
                'version_number' => $nextVersionNumber,
                'locale' => $locale,
                'content' => $content,
                'public_statement' => $isNonEditorial ? $publicStatement : null,
                'is_non_editorial_change' => $isNonEditorial,
                'effective_at' => $effectiveAt,
                'published_at' => now(),
                'published_by_user_id' => $publishedBy->id,
            ]);

            $pdfPath = "policy-versions/{$version->id}.pdf";
            $pdf = Pdf::loadView('pdf.policy.version', [
                'policy' => $lockedPolicy,
                'version' => $version,
            ]);
            StorageRole::private()->put($pdfPath, $pdf->output());
            $version->forceFill(['pdf_path' => $pdfPath])->save();

            if ($isNonEditorial) {
                $lockedPolicy->forceFill(['required_acceptance_version_id' => $version->id])->save();
            }

            PolicyVersionPublished::dispatch($version, $isNonEditorial, ! $isNonEditorial);

            return $version->fresh();
        });
    }
}
