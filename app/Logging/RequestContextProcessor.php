<?php

namespace App\Logging;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Injects per-request context into every log record:
 * request_id, user_id, client IP, HTTP method/path, and environment metadata.
 *
 * Octane-safe: reads from the current request via the container rather than
 * a static singleton so each Octane worker sees its own request context.
 */
class RequestContextProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $extra = array_merge($record->extra, [
            'env' => app()->environment(),
            'app' => config('app.name'),
        ]);

        if (app()->bound('request')) {
            $request = app('request');

            if ($request instanceof Request) {
                $extra['request_id'] = $request->attributes->get('request_id')
                    ?? $request->header('X-Request-ID');
                $extra['ip'] = $request->ip();
                $extra['http_method'] = $request->method();
                $extra['http_path'] = $request->path();
            }
        }

        if (Auth::check()) {
            $extra['user_id'] = Auth::id();
        }

        return $record->with(extra: $extra);
    }
}
