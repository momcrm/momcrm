<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lets an invitation name a group the person joins when they accept.
     */
    public function up(): void
    {
        Schema::table('team_invitations', function (Blueprint $table) {
            $table->foreignUuid('group_id')->nullable()->after('role')->constrained('groups')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_invitations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
        });
    }
};
