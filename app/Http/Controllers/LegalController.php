<?php

namespace App\Http\Controllers;

use App\Domain\Policy\Models\Policy;
use App\Models\OrganizationSetting;
use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    public function index(): Response
    {
        $policies = Policy::query()
            ->active()
            ->with('currentVersion:id,policy_id,version_number,locale,published_at')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'key', 'name', 'description'])
            ->map(fn (Policy $policy) => [
                'key' => $policy->key,
                'name' => $policy->name,
                'description' => $policy->description,
                'current_version' => $policy->currentVersion ? [
                    'version_number' => $policy->currentVersion->version_number,
                    'locale' => $policy->currentVersion->locale,
                    'published_at' => $policy->currentVersion->published_at?->toIso8601String(),
                ] : null,
            ]);

        return Inertia::render('legal/Index', [
            'policies' => $policies,
        ]);
    }

    public function impressum(): Response
    {
        $settings = OrganizationSetting::asArray();

        return Inertia::render('legal/Impressum', [
            'content' => $settings['impressum_content'] ?? null,
            'organization' => [
                'name' => $settings['name'] ?? null,
                'address_line1' => $settings['address_line1'] ?? null,
                'address_line2' => $settings['address_line2'] ?? null,
                'email' => $settings['email'] ?? null,
                'phone' => $settings['phone'] ?? null,
                'website' => $settings['website'] ?? null,
                'tax_id' => $settings['tax_id'] ?? null,
                'registration_id' => $settings['registration_id'] ?? null,
                'responsible' => $settings['impressum_responsible'] ?? null,
            ],
        ]);
    }
}
