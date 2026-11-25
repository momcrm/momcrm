<?php

use App\Shared\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Direct messages between members of a team: a conversation, who is in it
 * and how far each of them has read, and its messages.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(Table::Conversations->value, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->constrained(Table::Teams->value)->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'last_message_at']);
        });

        Schema::create(Table::ConversationParticipants->value, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained(Table::Conversations->value)->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained(Table::Users->value)->cascadeOnDelete();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create(Table::Messages->value, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->constrained(Table::Teams->value)->cascadeOnDelete();
            $table->foreignUuid('conversation_id')->constrained(Table::Conversations->value)->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained(Table::Users->value)->nullOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(Table::Messages->value);
        Schema::dropIfExists(Table::ConversationParticipants->value);
        Schema::dropIfExists(Table::Conversations->value);
    }
};
