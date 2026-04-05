<?php

namespace App\Domain\Orchestration\Http\Controllers;

use App\Domain\Api\Clients\Tmt2Client;
use App\Domain\Orchestration\Models\GameServer;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin page for managing external API connections (TMT2, future: Pelican, Steam).
 */
class ExternalApiController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', GameServer::class);

        return Inertia::render('orchestration/apis/Index', [
            'connections' => [
                'tmt2' => [
                    'enabled' => config('tmt2.enabled'),
                    'base_url' => config('tmt2.base_url'),
                    'has_token' => config('tmt2.token') !== null && config('tmt2.token') !== '',
                    'timeout' => config('tmt2.timeout', 5),
                    'retries' => config('tmt2.retries', 2),
                ],
            ],
        ]);
    }

    public function testTmt2(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', GameServer::class);

        try {
            $client = app(Tmt2Client::class);
            $result = $client->login();

            if ($result) {
                return back()->with('flash', ['tmt2_status' => 'connected']);
            }

            return back()->with('flash', ['tmt2_status' => 'auth_failed']);
        } catch (\Throwable $e) {
            return back()->with('flash', ['tmt2_status' => 'unreachable', 'tmt2_error' => $e->getMessage()]);
        }
    }
}
