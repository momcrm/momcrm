<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A ticket opened by an email remembers the mailbox it came in through and who
 * wrote in, so it can be answered even when the sender is not a contact.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignUuid('mailbox_id')->nullable()->after('group_id')->constrained('mailboxes')->nullOnDelete();
            $table->string('requester_name')->nullable()->after('mailbox_id');
            $table->string('requester_email')->nullable()->after('requester_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mailbox_id');
            $table->dropColumn(['requester_name', 'requester_email']);
        });
    }
};
