<?php

namespace App\Console\Commands\Notification;

use App\Domain\Ticketing\Enums\TicketSalePhase;
use App\Domain\Ticketing\Models\TicketType;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only forecast: lists every TicketType whose release or end-window will
 * fire within the given horizon, alongside the per-channel recipient counts
 * the dispatcher would currently produce.
 *
 * @see docs/mil-std-498/SDD.md §5.13
 */
#[Signature('notifications:preview-ticket-sale {--horizon-days=30 : Look-ahead window in days}')]
#[Description('Preview how many ticket-sale notifications will fire in the near future')]
class PreviewTicketSaleNotificationsCommand extends Command
{
    public function handle(): int
    {
        $horizonDays = max(1, (int) $this->option('horizon-days'));
        $horizon = now()->addDays($horizonDays);

        $rows = collect();

        TicketType::query()
            ->with('event')
            ->whereNotNull('purchase_from')
            ->where('notify_on_release', true)
            ->whereNull('release_notified_at')
            ->where('purchase_from', '<=', $horizon)
            ->whereHas('event', fn ($q) => $q->published())
            ->get()
            ->each(function (TicketType $type) use ($rows) {
                $rows->push($this->row($type, TicketSalePhase::Release, $type->purchase_from));
            });

        TicketType::query()
            ->with('event')
            ->whereNotNull('purchase_until')
            ->where('notify_on_end', true)
            ->whereNull('end_notified_at')
            ->where('purchase_until', '>', now())
            ->whereHas('event', fn ($q) => $q->published())
            ->get()
            ->each(function (TicketType $type) use ($rows, $horizon) {
                $fires = $type->purchase_until->subMinutes($type->notify_on_end_lead_minutes);

                if ($fires->lte($horizon)) {
                    $rows->push($this->row($type, TicketSalePhase::End, $fires));
                }
            });

        if ($rows->isEmpty()) {
            $this->info("No ticket-sale notifications planned within {$horizonDays} days.");

            return self::SUCCESS;
        }

        $rows = $rows->sortBy('fires_at_sort')->values();

        $this->table(
            ['Type', 'Event', 'Phase', 'Fires At', 'Push', 'Mail'],
            $rows->map(fn (array $r) => [
                $r['type_name'],
                $r['event_name'],
                $r['phase'],
                $r['fires_at'],
                $r['push_count'],
                $r['mail_count'],
            ])->all(),
        );

        $this->newLine();
        $this->info(sprintf(
            'Totals: %d push notifications, %d mail notifications across %d trigger(s).',
            $rows->sum('push_count'),
            $rows->sum('mail_count'),
            $rows->count(),
        ));

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function row(TicketType $type, TicketSalePhase $phase, CarbonInterface $firesAt): array
    {
        $applySuppression = function (Builder $query) use ($type, $phase): void {
            if ($phase !== TicketSalePhase::Release) {
                return;
            }

            $query
                ->whereDoesntHave(
                    'ownedTickets',
                    fn ($q) => $q->where('ticket_type_id', $type->id),
                )
                ->whereDoesntHave(
                    'assignedTickets',
                    fn ($q) => $q->where('ticket_type_id', $type->id),
                );
        };

        $pushQuery = User::query()
            ->whereHas(
                'notificationPreference',
                fn ($q) => $q->where('push_on_ticket_sale', true),
            )
            ->whereHas('pushSubscriptions');
        $applySuppression($pushQuery);

        $mailQuery = User::query()
            ->whereHas(
                'notificationPreference',
                fn ($q) => $q->where('mail_on_ticket_sale', true),
            );
        $applySuppression($mailQuery);

        return [
            'type_name' => $type->name,
            'event_name' => $type->event?->name ?? '—',
            'phase' => $phase->value,
            'fires_at' => $firesAt->toDateTimeString(),
            'fires_at_sort' => $firesAt->getTimestamp(),
            'push_count' => $pushQuery->count(),
            'mail_count' => $mailQuery->count(),
        ];
    }
}
