<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A mailbox is now read and sent through a connector, which holds the sign-in
 * the mailbox used to keep itself. Nothing is in production yet, so mailboxes
 * connected the old way are removed and not converted: they are added again
 * through a connector. Their tickets and messages stay.
 *
 * A message's id at the outside service is no longer Microsoft's alone.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('mailboxes')->delete();

        Schema::table('mailboxes', function (Blueprint $table) {
            $table->foreignUuid('connector_id')->after('team_id')->constrained('connectors')->cascadeOnDelete();
        });

        Schema::table('mailboxes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('connected_by_id');
        });

        Schema::table('mailboxes', function (Blueprint $table) {
            $table->dropColumn(['refresh_token', 'status']);
        });

        Schema::table('mailbox_messages', function (Blueprint $table) {
            $table->renameColumn('microsoft_id', 'external_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('mailboxes')->delete();

        Schema::table('mailbox_messages', function (Blueprint $table) {
            $table->renameColumn('external_id', 'microsoft_id');
        });

        Schema::table('mailboxes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('connector_id');
        });

        Schema::table('mailboxes', function (Blueprint $table) {
            $table->string('status')->default('connected');
            $table->text('refresh_token');
            $table->foreignUuid('connected_by_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }
};
