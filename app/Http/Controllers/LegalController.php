<?php

namespace App\Http\Controllers;

use App\Models\OrganizationSetting;
use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
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

    public function privacy(): Response
    {
        $settings = OrganizationSetting::asArray();

        return Inertia::render('legal/Privacy', [
            'content' => $settings['privacy_content'] ?? null,
            'organization' => [
                'name' => $settings['name'] ?? null,
                'email' => $settings['email'] ?? null,
            ],
        ]);
    }
}
