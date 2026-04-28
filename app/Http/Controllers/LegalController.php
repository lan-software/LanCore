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
        $locale = (string) app()->getLocale();

        $policies = Policy::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'key', 'name', 'description'])
            ->map(function (Policy $policy) use ($locale): array {
                $version = $policy->currentVersionFor($locale);

                return [
                    'key' => $policy->key,
                    'name' => $policy->name,
                    'description' => $policy->description,
                    'current_version' => $version ? [
                        'version_number' => $version->version_number,
                        'locale' => $version->locale,
                        'published_at' => $version->published_at?->toIso8601String(),
                    ] : null,
                ];
            });

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
