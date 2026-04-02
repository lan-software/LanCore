<?php

namespace App\Domain\Announcement\Http\Controllers;

use App\Domain\Announcement\Models\Announcement;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * @see docs/mil-std-498/SSS.md CAP-ANN-003
 * @see docs/mil-std-498/SRS.md ANN-F-003
 */
class AnnouncementDismissalController extends Controller
{
    public function store(Request $request, Announcement $announcement): RedirectResponse
    {
        $request->user()->dismissedAnnouncements()->syncWithoutDetaching([$announcement->id]);

        return back();
    }
}
