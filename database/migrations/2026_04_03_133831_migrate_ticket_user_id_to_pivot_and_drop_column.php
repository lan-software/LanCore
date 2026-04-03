<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate existing user_id data to the ticket_user pivot table
        DB::table('tickets')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->each(function (object $ticket) {
                DB::table('ticket_user')->insert([
                    'ticket_id' => $ticket->id,
                    'user_id' => $ticket->user_id,
                    'checked_in_at' => $ticket->status === 'checked_in' ? $ticket->checked_in_at : null,
                    'created_at' => $ticket->created_at,
                    'updated_at' => $ticket->updated_at,
                ]);
            });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
        });

        // Migrate pivot data back to user_id (take the first user per ticket)
        DB::table('ticket_user')
            ->select('ticket_id', DB::raw('MIN(user_id) as user_id'))
            ->groupBy('ticket_id')
            ->orderBy('ticket_id')
            ->each(function (object $row) {
                DB::table('tickets')
                    ->where('id', $row->ticket_id)
                    ->update(['user_id' => $row->user_id]);
            });
    }
};
