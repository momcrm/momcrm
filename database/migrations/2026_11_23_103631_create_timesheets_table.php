<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Timesheets: a member's hours for one pay period, submitted to and
     * approved by an owner or admin. A period with no row is still open.
     * Approving one stores its totals and locks its shifts, so later changes
     * to schedules don't alter what was paid. Teams choose weekly or
     * fortnightly periods; a fortnight counts from `timesheet_starts_on`.
     */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('timesheet_period')->default('weekly');
            $table->date('timesheet_starts_on')->nullable();
        });

        Schema::create('timesheets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 500)->nullable();
            $table->json('totals')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'user_id', 'starts_on']);
            $table->index(['team_id', 'starts_on', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timesheets');

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['timesheet_period', 'timesheet_starts_on']);
        });
    }
};
