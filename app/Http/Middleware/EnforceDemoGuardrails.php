<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceDemoGuardrails
{
    /**
     * @var list<array{method: string, path: string}>
     */
    private const BLOCKED = [
        ['method' => 'POST', 'path' => 'register'],
        ['method' => 'PUT', 'path' => 'user/profile-information'],
        ['method' => 'PUT', 'path' => 'user/password'],
        ['method' => 'DELETE', 'path' => 'user'],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.demo')) {
            return $next($request);
        }

        foreach (self::BLOCKED as $rule) {
            if ($request->isMethod($rule['method']) && $request->is($rule['path'])) {
                abort(403, 'Action disabled in demo mode.');
            }
        }

        return $next($request);
    }
}
