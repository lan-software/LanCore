<?php

namespace App\Domain\Announcement\Http\Controllers;

use App\Domain\Announcement\Models\Announcement;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AnnouncementDismissalController extends Controller
{
    public function store(Request $request, Announcement $announcement): RedirectResponse
    {
        $request->user()->dismissedAnnouncements()->syncWithoutDetaching([$announcement->id]);

        return back();
    }
}
