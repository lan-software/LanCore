<?php

namespace App\Domain\Policy\Http\Controllers;

use App\Domain\Policy\Actions\ArchivePolicy;
use App\Domain\Policy\Actions\CreatePolicy;
use App\Domain\Policy\Actions\UpdatePolicy;
use App\Domain\Policy\Http\Requests\StorePolicyRequest;
use App\Domain\Policy\Http\Requests\UpdatePolicyRequest;
use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyType;
use App\Domain\Policy\Models\PolicyVersion;
use App\Domain\Policy\Support\PolicyVersionDiff;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SSS.md CAP-POL-001
 * @see docs/mil-std-498/SRS.md POL-F-001..005
 */
class PolicyController extends Controller
{
    public function __construct(
        private readonly CreatePolicy $createPolicy,
        private readonly UpdatePolicy $updatePolicy,
        private readonly ArchivePolicy $archivePolicy,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Policy::class);

        $policies = Policy::with(['type', 'currentVersion'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/policies/Index', [
            'policies' => $policies,
            'policyTypes' => PolicyType::orderBy('label')->get(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Policy::class);

        return Inertia::render('admin/policies/Create', [
            'policyTypes' => PolicyType::orderBy('label')->get(),
        ]);
    }

    public function store(StorePolicyRequest $request): RedirectResponse
    {
        $this->authorize('create', Policy::class);

        $policy = $this->createPolicy->execute($request->validated());

        return redirect()->route('admin.policies.show', $policy)
            ->with('success', __('policies.flash.created'));
    }

    public function show(Policy $policy): Response
    {
        $this->authorize('view', $policy);

        $policy->load([
            'type',
            'requiredAcceptanceVersion',
            'versions' => fn ($q) => $q->orderByDesc('version_number'),
            'versions.publishedBy',
        ]);

        return Inertia::render('admin/policies/Show', [
            'policy' => $policy,
            'audits' => $this->auditsFor($policy),
            'diffs' => $this->diffsFor($policy),
        ]);
    }

    /**
     * Build a flat audit-log feed across the Policy and every related
     * PolicyVersion. Read-only — sourced from `audits` (laravel-auditing).
     *
     * @return list<array<string, mixed>>
     */
    private function auditsFor(Policy $policy): array
    {
        $versionIds = $policy->versions->pluck('id')->all();

        $rows = DB::table('audits')
            ->where(function ($q) use ($policy): void {
                $q->where('auditable_type', Policy::class)
                    ->where('auditable_id', $policy->id);
            })
            ->orWhere(function ($q) use ($versionIds): void {
                if ($versionIds === []) {
                    return;
                }
                $q->where('auditable_type', PolicyVersion::class)
                    ->whereIn('auditable_id', $versionIds);
            })
            ->leftJoin('users', 'audits.user_id', '=', 'users.id')
            ->orderByDesc('audits.created_at')
            ->limit(200)
            ->get([
                'audits.id',
                'audits.event',
                'audits.auditable_type',
                'audits.auditable_id',
                'audits.old_values',
                'audits.new_values',
                'audits.created_at',
                'users.id as actor_id',
                'users.name as actor_name',
            ]);

        return $rows->map(fn ($row) => [
            'id' => $row->id,
            'event' => $row->event,
            'auditable_type' => class_basename((string) $row->auditable_type),
            'auditable_id' => $row->auditable_id,
            'actor' => $row->actor_id ? [
                'id' => $row->actor_id,
                'name' => $row->actor_name,
            ] : null,
            'old_values' => $this->decodeJson($row->old_values),
            'new_values' => $this->decodeJson($row->new_values),
            'created_at' => $row->created_at,
        ])->all();
    }

    /**
     * Pre-compute HTML diffs between every consecutive published version
     * (within the same locale) so the Show page can render them inline
     * without an extra round-trip.
     *
     * @return list<array{from_version:int,to_version:int,locale:string,html:string}>
     */
    private function diffsFor(Policy $policy): array
    {
        $byLocale = $policy->versions->groupBy('locale');
        $diffs = [];

        foreach ($byLocale as $locale => $versions) {
            $sorted = $versions->sortBy('version_number')->values();

            for ($i = 1, $n = $sorted->count(); $i < $n; $i++) {
                /** @var PolicyVersion $from */
                $from = $sorted[$i - 1];
                /** @var PolicyVersion $to */
                $to = $sorted[$i];

                $diffs[] = [
                    'from_version' => $from->version_number,
                    'to_version' => $to->version_number,
                    'locale' => (string) $locale,
                    'html' => PolicyVersionDiff::render($from->content, $to->content),
                ];
            }
        }

        usort(
            $diffs,
            fn (array $a, array $b) => $b['to_version'] <=> $a['to_version'],
        );

        return $diffs;
    }

    private function decodeJson(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : $value;
        }

        return $value;
    }

    public function edit(Policy $policy): Response
    {
        $this->authorize('update', $policy);

        $policy->load(['type']);

        return Inertia::render('admin/policies/Edit', [
            'policy' => $policy,
            'policyTypes' => PolicyType::orderBy('label')->get(),
        ]);
    }

    public function update(UpdatePolicyRequest $request, Policy $policy): RedirectResponse
    {
        $this->authorize('update', $policy);

        $this->updatePolicy->execute($policy, $request->validated());

        return back()->with('success', __('policies.flash.updated'));
    }

    public function archive(Policy $policy): RedirectResponse
    {
        $this->authorize('delete', $policy);

        $this->archivePolicy->execute($policy);

        return redirect()->route('admin.policies.index')
            ->with('success', __('policies.flash.archived'));
    }
}
