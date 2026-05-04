<?php

namespace App\Domain\Theme\Http\Controllers;

use App\Domain\Theme\Actions\CreateTheme;
use App\Domain\Theme\Actions\DeleteTheme;
use App\Domain\Theme\Actions\SetDefaultTheme;
use App\Domain\Theme\Actions\UpdateTheme;
use App\Domain\Theme\Http\Requests\StoreThemeRequest;
use App\Domain\Theme\Http\Requests\UpdateThemeRequest;
use App\Domain\Theme\Models\Theme;
use App\Domain\Theme\Support\PaletteVariables;
use App\Http\Controllers\Controller;
use App\Models\OrganizationSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SSS.md CAP-THM-001
 * @see docs/mil-std-498/SRS.md THM-F-001
 */
class ThemeController extends Controller
{
    public function __construct(
        private readonly CreateTheme $createTheme,
        private readonly UpdateTheme $updateTheme,
        private readonly DeleteTheme $deleteTheme,
        private readonly SetDefaultTheme $setDefaultTheme,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Theme::class);

        $defaultThemeId = OrganizationSetting::get('default_theme_id');

        return Inertia::render('themes/Index', [
            'themes' => Theme::query()->orderBy('name')->get(['id', 'name', 'description']),
            'defaultThemeId' => $defaultThemeId === null ? null : (int) $defaultThemeId,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Theme::class);

        return Inertia::render('themes/Create', [
            'paletteVariables' => PaletteVariables::all(),
        ]);
    }

    public function store(StoreThemeRequest $request): RedirectResponse
    {
        $this->authorize('create', Theme::class);

        $this->createTheme->execute($request->validated());

        return redirect()->route('themes.index');
    }

    public function edit(Theme $theme): Response
    {
        $this->authorize('update', $theme);

        return Inertia::render('themes/Edit', [
            'theme' => $theme,
            'paletteVariables' => PaletteVariables::all(),
        ]);
    }

    public function update(UpdateThemeRequest $request, Theme $theme): RedirectResponse
    {
        $this->authorize('update', $theme);

        $this->updateTheme->execute($theme, $request->validated());

        return redirect()->route('themes.index');
    }

    public function destroy(Theme $theme): RedirectResponse
    {
        $this->authorize('delete', $theme);

        $this->deleteTheme->execute($theme);

        return redirect()->route('themes.index');
    }

    public function setDefault(Request $request): RedirectResponse
    {
        $this->authorize('create', Theme::class);

        $validated = $request->validate([
            'theme_id' => ['nullable', 'integer', Rule::exists('themes', 'id')],
        ]);

        $this->setDefaultTheme->execute($validated['theme_id'] ?? null);

        return back();
    }
}
