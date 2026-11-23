<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The schedule a member works to. Null follows the team's default, which
     * is also where members go when their schedule is deleted.
     */
    public function up(): void
    {
        Schema::table('team_user', function (Blueprint $table) {
            $table->foreignUuid('work_schedule_id')->nullable()->after('role')->constrained('work_schedules')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_user', function (Blueprint $table) {
            $table->dropConstrainedForeignId('work_schedule_id');
        });
    }
};
