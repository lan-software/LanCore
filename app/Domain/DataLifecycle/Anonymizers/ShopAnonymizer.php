<?php

namespace App\Domain\DataLifecycle\Anonymizers;

use App\Domain\DataLifecycle\Anonymizers\Contracts\DomainAnonymizer;
use App\Domain\DataLifecycle\DTOs\AnonymizationResult;
use App\Domain\DataLifecycle\Enums\AnonymizationMode;
use App\Domain\DataLifecycle\Enums\RetentionDataClass;
use App\Domain\DataLifecycle\Models\RetentionPolicy;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Orders, OrderLines and Carts. The financial header (amounts, currency,
 * invoice_number, paid_at) is preserved under retention. Snapshot-of-customer
 * fields stored in `metadata` (stripe customer details, billing address) are
 * scrubbed of identifying keys.
 *
 * On PurgeNow, orders are hard-deleted unless the retention policy is pinned.
 */
final class ShopAnonymizer implements DomainAnonymizer
{
    private const PII_METADATA_KEYS = [
        'customer_email', 'customer_name', 'customer_phone', 'customer_address',
        'billing_email', 'billing_name', 'billing_address', 'billing_phone',
        'stripe_customer_email', 'stripe_customer_name',
    ];

    public function dataClass(): RetentionDataClass
    {
        return RetentionDataClass::ShopOrder;
    }

    public function anonymize(User $user, AnonymizationMode $mode): AnonymizationResult
    {
        if (! Schema::hasTable('orders')) {
            return AnonymizationResult::nothingToDo();
        }

        if ($mode === AnonymizationMode::PurgeNow) {
            $policy = $this->maybePolicy();
            if ($policy !== null && ! $policy->can_be_force_deleted) {
                $mode = AnonymizationMode::Anonymize;
            }
        }

        if ($mode === AnonymizationMode::PurgeNow) {
            $orderIds = DB::table('orders')->where('user_id', $user->getKey())->pluck('id')->all();

            if ($orderIds === []) {
                return AnonymizationResult::nothingToDo();
            }

            $deletedLines = Schema::hasTable('order_lines')
                ? DB::table('order_lines')->whereIn('order_id', $orderIds)->delete()
                : 0;
            $deletedOrders = DB::table('orders')->whereIn('id', $orderIds)->delete();

            return new AnonymizationResult(
                recordsScrubbed: $deletedOrders + $deletedLines,
                recordsKeptUnderRetention: 0,
                retentionUntil: null,
                summary: ['orders_deleted' => $deletedOrders, 'order_lines_deleted' => $deletedLines],
            );
        }

        $scrubbedRows = 0;

        DB::table('orders')
            ->where('user_id', $user->getKey())
            ->orderBy('id')
            ->lazyById(200)
            ->each(function (object $row) use (&$scrubbedRows): void {
                $metadata = $row->metadata !== null ? json_decode((string) $row->metadata, true) : null;
                if (! is_array($metadata)) {
                    return;
                }

                $changed = false;
                foreach (self::PII_METADATA_KEYS as $key) {
                    if (array_key_exists($key, $metadata)) {
                        $metadata[$key] = null;
                        $changed = true;
                    }
                }

                if ($changed) {
                    DB::table('orders')->where('id', $row->id)->update(['metadata' => json_encode($metadata)]);
                    $scrubbedRows++;
                }
            });

        if (Schema::hasTable('carts')) {
            DB::table('carts')->where('user_id', $user->getKey())->delete();
        }

        return new AnonymizationResult(
            recordsScrubbed: 0,
            recordsKeptUnderRetention: DB::table('orders')->where('user_id', $user->getKey())->count(),
            retentionUntil: $this->retentionUntil(),
            summary: ['orders_metadata_scrubbed' => $scrubbedRows],
        );
    }

    private function maybePolicy(): ?RetentionPolicy
    {
        return RetentionPolicy::query()->where('data_class', $this->dataClass()->value)->first();
    }

    private function retentionUntil(): ?CarbonInterface
    {
        $policy = $this->maybePolicy();

        return $policy === null ? null : now()->addDays($policy->retention_days);
    }
}
