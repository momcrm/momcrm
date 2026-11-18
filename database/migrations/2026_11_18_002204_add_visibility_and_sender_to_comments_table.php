<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A comment is either an internal note or part of the conversation with the
 * customer. One that arrived by email has no author in the team, so it keeps
 * the name and address of whoever sent it.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->string('visibility')->default('internal')->after('body');
            $table->string('sender_name')->nullable()->after('visibility');
            $table->string('sender_email')->nullable()->after('sender_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn(['visibility', 'sender_name', 'sender_email']);
        });
    }
};
