<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A break taken during a shift. Like shifts, `is_open` lets the unique
     * index allow only one break at a time per shift.
     */
    public function up(): void
    {
        Schema::create('shift_breaks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignUuid('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedTinyInteger('is_open')->nullable()->storedAs('case when ended_at is null then 1 end');
            $table->timestamps();

            $table->unique(['shift_id', 'is_open']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_breaks');
    }
};
