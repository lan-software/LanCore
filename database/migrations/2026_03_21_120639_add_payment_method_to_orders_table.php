<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method')->default('stripe')->after('id');
            $table->json('metadata')->nullable()->after('voucher_id');
            $table->renameColumn('stripe_checkout_session_id', 'provider_session_id');
            $table->renameColumn('stripe_payment_intent_id', 'provider_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('provider_session_id', 'stripe_checkout_session_id');
            $table->renameColumn('provider_transaction_id', 'stripe_payment_intent_id');
            $table->dropColumn(['payment_method', 'metadata']);
        });
    }
};
