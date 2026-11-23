<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A shift runs from clocking in to clocking out. While it is open,
     * `is_open` is 1 and the unique index stops a second open shift for the
     * same person; closed shifts hold NULL there, which never collides.
     */
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('work_date');
            $table->timestamp('clocked_in_at');
            $table->timestamp('clocked_out_at')->nullable();
            $table->string('clock_in_ip', 45)->nullable();
            $table->string('note')->nullable();
            $table->unsignedTinyInteger('is_open')->nullable()->storedAs('case when clocked_out_at is null then 1 end');
            $table->timestamps();

            $table->unique(['team_id', 'user_id', 'is_open']);
            $table->index(['team_id', 'work_date']);
            $table->index(['team_id', 'user_id', 'work_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
