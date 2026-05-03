<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_messages', function (Blueprint $table): void {
            $table->id();

            $table->string('message_id')->nullable()->index();
            $table->string('mailer')->nullable();

            $table->string('from_address')->nullable();
            $table->string('from_name')->nullable();
            $table->json('to_addresses')->nullable();
            $table->json('cc_addresses')->nullable();
            $table->json('bcc_addresses')->nullable();

            $table->string('subject')->nullable();
            $table->longText('html_body')->nullable();
            $table->longText('text_body')->nullable();
            $table->json('headers')->nullable();
            $table->json('tags')->nullable();

            $table->string('status')->default('sent')->index();
            $table->text('error')->nullable();

            $table->string('source')->nullable()->index();
            $table->string('source_label')->nullable();

            $table->nullableMorphs('notifiable');

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('email_messages')
                ->nullOnDelete();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_messages');
    }
};
