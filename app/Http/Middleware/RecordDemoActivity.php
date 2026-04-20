<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RecordDemoActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.demo') && $request->user() !== null) {
            try {
                Redis::set('demo:last_activity', (string) now()->timestamp);
            } catch (Throwable $e) {
                Log::debug('[demo] failed to record activity', ['error' => $e->getMessage()]);
            }
        }

        return $next($request);
    }
}
