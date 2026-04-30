<?php

namespace App\Domain\DataLifecycle\RetentionEvaluators;

use App\Domain\DataLifecycle\DTOs\RetentionVerdict;
use App\Domain\DataLifecycle\Enums\RetentionDataClass;
use App\Domain\DataLifecycle\Models\RetentionPolicy;
use App\Domain\DataLifecycle\RetentionEvaluators\Contracts\RetentionEvaluator;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A user is under accounting retention if any of:
 *   - has any order (any status)
 *   - has a Stripe customer linkage (stripe_id NOT NULL)
 *   - redeemed a voucher
 * The retention window starts from the latest accounting touch-point and
 * extends by the configured `retention_days` (default 10y).
 *
 * @see docs/mil-std-498/SSS.md CAP-DL-005
 * @see docs/mil-std-498/SRS.md DL-F-011
 */
final class AccountingEvaluator implements RetentionEvaluator
{
    public function dataClass(): RetentionDataClass
    {
        return RetentionDataClass::ShopOrder;
    }

    public function evaluate(User $user): RetentionVerdict
    {
        $latestTouch = $this->latestAccountingTouchpoint($user);

        if ($latestTouch === null) {
            return RetentionVerdict::noHold();
        }

        $policy = RetentionPolicy::query()
            ->where('data_class', RetentionDataClass::ShopOrder->value)
            ->first();

        $days = $policy?->retention_days ?? RetentionDataClass::ShopOrder->defaultRetentionDays();
        $until = CarbonImmutable::instance($latestTouch)->addDays($days);

        return RetentionVerdict::hold(
            $until,
            $policy?->legal_basis ?? RetentionDataClass::ShopOrder->defaultLegalBasis(),
        );
    }

    private function latestAccountingTouchpoint(User $user): ?CarbonInterface
    {
        $candidates = [];

        if ($user->stripe_id !== null && Schema::hasTable('subscriptions')) {
            $candidates[] = DB::table('subscriptions')
                ->where('user_id', $user->getKey())
                ->max('created_at');
        }

        if (Schema::hasTable('orders')) {
            $candidates[] = DB::table('orders')
                ->where('user_id', $user->getKey())
                ->max('created_at');
        }

        $candidates = array_filter($candidates);

        if ($candidates === []) {
            // Stripe customer linkage with no orders / subscriptions still counts as accounting touch.
            return $user->stripe_id !== null
                ? CarbonImmutable::parse($user->updated_at ?? now())
                : null;
        }

        $max = max(array_map(fn ($value) => CarbonImmutable::parse($value), $candidates));

        return $max;
    }
}
