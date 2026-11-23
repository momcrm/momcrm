<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The hours a team expects people to work, such as "Mon–Fri 9–5". `days`
     * holds each weekday's start, end and unpaid break, or null for a day off.
     * Members without one of their own follow the team's default.
     */
    public function up(): void
    {
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('name', 100);
            $table->boolean('is_default')->default(false);
            $table->json('days');
            $table->unsignedSmallInteger('grace_minutes')->default(5);
            $table->timestamps();

            $table->index(['team_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_schedules');
    }
};
