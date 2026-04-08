<?php

namespace App\Domain\Ticketing\Http\Controllers\Api;

use App\Domain\Ticketing\Security\TicketKeyRing;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * @see docs/mil-std-498/IDD.md §3.11
 * @see docs/mil-std-498/SSS.md SEC-018
 */
class SigningKeysController extends Controller
{
    public function __invoke(TicketKeyRing $keyRing): JsonResponse
    {
        return response()
            ->json($keyRing->toJwks())
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
