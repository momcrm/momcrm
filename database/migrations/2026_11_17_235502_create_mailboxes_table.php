<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Microsoft 365 mailboxes a team has connected, so mail sent to them can
 * become tickets. The refresh token is stored encrypted; synced_through is how
 * far the inbox has been read.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mailboxes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('address');
            $table->string('display_name')->nullable();
            $table->boolean('is_shared')->default(false);
            $table->string('status')->default('connected');
            $table->foreignUuid('group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->boolean('auto_create_tickets')->default(true);
            $table->text('refresh_token');
            $table->timestamp('synced_through')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->foreignUuid('connected_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mailboxes');
    }
};
