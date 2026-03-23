<?php

namespace App\Providers;

use App\Domain\Event\Models\Event;
use App\Domain\Ticketing\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\ServiceProvider;
use Spatie\Prometheus\Collectors\Queue\QueueDelayedJobsCollector;
use Spatie\Prometheus\Collectors\Queue\QueueOldestPendingJobCollector;
use Spatie\Prometheus\Collectors\Queue\QueuePendingJobsCollector;
use Spatie\Prometheus\Collectors\Queue\QueueReservedJobsCollector;
use Spatie\Prometheus\Collectors\Queue\QueueSizeCollector;
use Spatie\Prometheus\Facades\Prometheus;

class PrometheusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerAppMetrics();
        $this->registerHttpMetrics();
        $this->registerQueueCollectors(
            queues: explode(',', (string) env('METRICS_QUEUES', 'default')),
            connection: env('QUEUE_CONNECTION', 'database'),
        );
    }

    // -------------------------------------------------------------------------
    // Application domain gauges
    // -------------------------------------------------------------------------

    private function registerAppMetrics(): void
    {
        Prometheus::addGauge('Registered users total')
            ->name('users_total')
            ->helpText('Total number of registered users.')
            ->value(fn () => User::count());

        Prometheus::addGauge('Active events total')
            ->name('events_total')
            ->helpText('Total number of events in the system.')
            ->value(fn () => Event::count());

        Prometheus::addGauge('Tickets sold total')
            ->name('tickets_total')
            ->helpText('Total number of tickets sold (all statuses).')
            ->value(fn () => Ticket::count());

        Prometheus::addGauge('Failed jobs total')
            ->name('failed_jobs_total')
            ->helpText('Total number of entries in the failed_jobs table.')
            ->value(fn () => DB::table('failed_jobs')->count());
    }

    // -------------------------------------------------------------------------
    // HTTP request metrics (incremented per-request by TrackHttpMetrics middleware)
    // -------------------------------------------------------------------------

    private function registerHttpMetrics(): void
    {
        Prometheus::addGauge('HTTP requests total')
            ->name('http_requests_total')
            ->helpText('Total HTTP requests grouped by method and status code.')
            ->label('method')
            ->label('status_code')
            ->value(function (): array {
                /** @var array<string, string> $raw */
                $raw = Redis::hGetAll('metrics:http:requests');

                if (empty($raw)) {
                    return [];
                }

                return collect($raw)
                    ->map(function (string $count, string $field): array {
                        [$method, $statusCode] = explode('_', $field, 2);

                        return [(int) $count, [$method, $statusCode]];
                    })
                    ->values()
                    ->all();
            });

        Prometheus::addGauge('HTTP request duration EMA (ms)')
            ->name('http_request_duration_ms_ema')
            ->helpText('Exponential moving average of HTTP response time per route (in ms).')
            ->label('method')
            ->label('route')
            ->value(function (): array {
                /** @var array<string, string> $raw */
                $raw = Redis::hGetAll('metrics:http:duration_ms');

                if (empty($raw)) {
                    return [];
                }

                return collect($raw)
                    ->map(function (string $ema, string $field): array {
                        [$method, $route] = explode('_', $field, 2);

                        return [(float) $ema, [$method, $route]];
                    })
                    ->values()
                    ->all();
            });
    }

    // -------------------------------------------------------------------------
    // Queue collectors (built-in spatie collectors)
    // -------------------------------------------------------------------------

    public function registerQueueCollectors(array $queues = [], ?string $connection = null): self
    {
        Prometheus::registerCollectorClasses([
            QueueSizeCollector::class,
            QueuePendingJobsCollector::class,
            QueueDelayedJobsCollector::class,
            QueueReservedJobsCollector::class,
            QueueOldestPendingJobCollector::class,
        ], compact('connection', 'queues'));

        return $this;
    }
}
