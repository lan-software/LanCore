<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class RecordDemoActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.demo') && $request->user() !== null) {
            Redis::set('demo:last_activity', (string) now()->timestamp);
        }

        return $next($request);
    }
}
