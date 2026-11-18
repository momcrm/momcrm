<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every email read from, or sent through, a connected mailbox, and the ticket
 * and comment it belongs to. A message is kept once per team by its
 * Message-ID, which is also how replies find the ticket they answer.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mailbox_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignUuid('mailbox_id')->nullable()->constrained('mailboxes')->nullOnDelete();
            $table->string('direction');
            $table->string('status');
            $table->string('microsoft_id', 500)->nullable();
            $table->string('internet_message_id', 500);
            $table->string('in_reply_to', 500)->nullable();
            $table->json('reference_ids')->nullable();
            $table->string('from_address')->nullable();
            $table->string('from_name')->nullable();
            $table->json('to_recipients')->nullable();
            $table->text('subject')->nullable();
            $table->longText('body_text')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->foreignUuid('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->foreignUuid('comment_id')->nullable()->constrained('comments')->nullOnDelete();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'internet_message_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mailbox_messages');
    }
};
