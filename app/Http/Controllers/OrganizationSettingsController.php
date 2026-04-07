<?php

namespace App\Http\Controllers;

use App\Models\OrganizationSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationSettingsController extends Controller
{
    public function index(): Response
    {
        $settings = OrganizationSetting::asArray();
        $logoPath = $settings['logo'] ?? null;

        return Inertia::render('settings/Organization', [
            'settings' => $settings,
            'logoUrl' => $logoPath ? Storage::url($logoPath) : null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'registration_id' => ['nullable', 'string', 'max:255'],
            'legal_notice' => ['nullable', 'string', 'max:2000'],
        ]);

        foreach ($validated as $key => $value) {
            OrganizationSetting::set($key, $value);
        }

        Cache::forget('inertia.organization');

        return back();
    }

    public function uploadLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
        ]);

        $oldPath = OrganizationSetting::get('logo');
        if ($oldPath) {
            Storage::delete($oldPath);
        }

        $path = $request->file('logo')->store('organization', 'public');
        OrganizationSetting::set('logo', $path);

        Cache::forget('inertia.organization');

        return back();
    }

    public function removeLogo(): RedirectResponse
    {
        $path = OrganizationSetting::get('logo');
        if ($path) {
            Storage::delete($path);
            OrganizationSetting::set('logo', null);
        }

        Cache::forget('inertia.organization');

        return back();
    }
}
