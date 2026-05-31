<?php

namespace App\Domain\Publishing\Http\Controllers;

use App\Concerns\HasModelCache;
use App\Domain\Publishing\Actions\BuildLanPartyDocument;
use App\Http\Controllers\Controller;
use App\Services\ModelCacheService;
use Illuminate\Http\JsonResponse;

/**
 * Serves the public, unauthenticated LAN Party Publishing Standard v2
 * document at `/.well-known/lan-party.json`. The document is built
 * just-in-time and cached under the `lpps` group, which is invalidated by
 * {@see HasModelCache} on any change to the underlying
 * organisation, venue, address, event, or ticket-type data.
 *
 * @see docs/mil-std-498/SSS.md CAP-PUB-001, CAP-PUB-002
 * @see docs/mil-std-498/SRS.md PUB-F-001, PUB-F-003
 * @see docs/mil-std-498/IDD.md §3.21
 */
class LanPartyPublishingController extends Controller
{
    public function __invoke(BuildLanPartyDocument $build, ModelCacheService $cache): JsonResponse
    {
        /** @var array<string, mixed> $document */
        $document = $cache->remember('lpps', 'document', fn (): array => $build->execute());

        return response()->json(
            $document,
            200,
            ['Content-Type' => 'application/json'],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }
}
