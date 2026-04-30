<?php

namespace App\Domain\DataLifecycle\Http\Controllers;

use App\Domain\Policy\Actions\Gdpr\GenerateGdprExport;
use App\Enums\Permission as RootPermission;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Web-side wrapper for the GDPR Article 15 export. Lets admins trigger an
 * export from the user detail page without dropping into the artisan console.
 *
 * @see docs/mil-std-498/SSS.md CAP-GDPR-001, CAP-DL-007
 * @see docs/mil-std-498/SRS.md GDPR-F-001
 */
class AdminUserGdprExportController extends Controller
{
    public function __construct(private readonly GenerateGdprExport $action) {}

    public function store(Request $request, User $user): BinaryFileResponse
    {
        if (! $request->user()->hasPermission(RootPermission::ExportUserPersonalData)) {
            abort(403);
        }

        $validated = $request->validate([
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'include_soft_deleted' => ['nullable', 'boolean'],
        ]);

        $result = $this->action->execute(
            $user,
            $validated['password'] ?? null,
            (bool) ($validated['include_soft_deleted'] ?? true),
            null,
        );

        return response()->download(
            $result->absoluteZipPath,
            basename($result->absoluteZipPath),
            ['Content-Type' => 'application/zip'],
        )->deleteFileAfterSend(false);
    }
}
