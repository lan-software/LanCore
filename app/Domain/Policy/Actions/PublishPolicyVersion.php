<?php

namespace App\Domain\Policy\Actions;

use App\Domain\Policy\Events\PolicyPublished;
use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyVersion;
use App\Models\User;
use App\Support\StorageRole;
use Barryvdh\DomPDF\Facade\Pdf;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Publish a new version of a policy across every locale draft atomically.
 *
 * Locks the parent Policy row, computes the next version_number across the
 * entire policy (not per-locale), renders + stores one PDF per locale, and
 * updates the gating pointer (required_acceptance_version_number) only when
 * the publish is flagged non-editorial. Editorial publishes are silent —
 * they bump version_number for audit history but do not force re-acceptance.
 *
 * @see docs/mil-std-498/SSS.md CAP-POL-003
 * @see docs/mil-std-498/SRS.md POL-F-008, POL-F-009, POL-F-016
 */
class PublishPolicyVersion
{
    /**
     * @return array<int, PolicyVersion> rows just created, keyed by locale
     */
    public function execute(
        Policy $policy,
        bool $isNonEditorial,
        ?string $publicStatement,
        User $publishedBy,
        ?DateTimeInterface $effectiveAt = null,
    ): array {
        $effectiveAt ??= now();

        return DB::transaction(function () use ($policy, $isNonEditorial, $publicStatement, $publishedBy, $effectiveAt): array {
            $lockedPolicy = Policy::query()->whereKey($policy->id)->lockForUpdate()->firstOrFail();

            $drafts = $lockedPolicy->drafts()->orderBy('locale')->get();

            if ($drafts->isEmpty()) {
                throw new RuntimeException('Cannot publish: policy has no locale drafts.');
            }

            foreach ($drafts as $draft) {
                if ((string) $draft->content === '') {
                    throw new RuntimeException("Cannot publish: draft for locale [{$draft->locale}] is empty.");
                }
            }

            $nextVersionNumber = (int) PolicyVersion::query()
                ->where('policy_id', $lockedPolicy->id)
                ->max('version_number') + 1;

            $created = [];
            $now = now();

            foreach ($drafts as $draft) {
                $version = PolicyVersion::create([
                    'policy_id' => $lockedPolicy->id,
                    'version_number' => $nextVersionNumber,
                    'locale' => $draft->locale,
                    'content' => $draft->content,
                    'public_statement' => $isNonEditorial ? $publicStatement : null,
                    'is_non_editorial_change' => $isNonEditorial,
                    'effective_at' => $effectiveAt,
                    'published_at' => $now,
                    'published_by_user_id' => $publishedBy->id,
                ]);

                $pdfPath = "policy-versions/{$version->id}.pdf";
                $pdf = Pdf::loadView('pdf.policy.version', [
                    'policy' => $lockedPolicy,
                    'version' => $version,
                ]);
                StorageRole::private()->put($pdfPath, $pdf->output());
                $version->forceFill(['pdf_path' => $pdfPath])->save();

                $created[] = $version->fresh();
            }

            if ($isNonEditorial) {
                $lockedPolicy->forceFill([
                    'required_acceptance_version_number' => $nextVersionNumber,
                ])->save();
            }

            PolicyPublished::dispatch(
                $lockedPolicy->fresh(),
                $nextVersionNumber,
                $isNonEditorial,
                ! $isNonEditorial,
            );

            return $created;
        });
    }
}
