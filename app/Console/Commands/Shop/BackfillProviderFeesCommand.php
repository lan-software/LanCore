<?php

namespace App\Console\Commands\Shop;

use App\Domain\Shop\Actions\PersistProviderFee;
use App\Domain\Shop\Enums\OrderStatus;
use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Models\Order;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Opt-in one-shot backfill of provider fees on historic paid orders.
 *
 * Sweeps orders that are `completed` and lack a `fees_fetched_at` timestamp,
 * dispatches each to {@see PersistProviderFee}, and reports counts. Use
 * `--dry-run` to preview, `--provider=stripe|paypal|on_site` to scope.
 *
 * @see docs/mil-std-498/SRS.md SHP-F-021
 */
#[Signature('shop:backfill-fees {--provider= : Restrict to a payment_method (stripe|paypal|on_site)} {--dry-run : Don\'t persist, just count what would change} {--limit=0 : Cap how many orders to touch (0 = no limit)}')]
#[Description('Backfill provider fees on historic paid orders that don\'t have fee data yet.')]
class BackfillProviderFeesCommand extends Command
{
    public function handle(PersistProviderFee $persist): int
    {
        $providerOpt = (string) ($this->option('provider') ?? '');
        $dryRun = (bool) $this->option('dry-run');
        $limit = (int) ($this->option('limit') ?? 0);

        $method = null;
        if ($providerOpt !== '') {
            $method = PaymentMethod::tryFrom($providerOpt);
            if ($method === null) {
                $this->error("Unknown provider: {$providerOpt}");

                return self::FAILURE;
            }
        }

        $query = Order::query()
            ->where('status', OrderStatus::Completed)
            ->whereNull('fees_fetched_at');

        if ($method !== null) {
            $query->where('payment_method', $method);
        }

        $totalCandidates = (clone $query)->count();
        $this->line("Found {$totalCandidates} order(s) eligible for backfill.");

        if ($totalCandidates === 0) {
            return self::SUCCESS;
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $filled = 0;
        $skipped = 0;

        foreach ($query->cursor() as $order) {
            if ($dryRun) {
                $filled++;

                continue;
            }

            $fee = $persist->execute($order);
            if ($fee === null) {
                $skipped++;
                $this->warn("  · Order #{$order->id}: provider did not report a fee — skipped.");

                continue;
            }

            $filled++;
            $this->info("  · Order #{$order->id}: fee={$fee->feeCents} {$fee->currency} ({$fee->source})");
        }

        $verb = $dryRun ? 'Would update' : 'Updated';
        $this->line("{$verb} {$filled} order(s); skipped {$skipped}.");

        return self::SUCCESS;
    }
}
