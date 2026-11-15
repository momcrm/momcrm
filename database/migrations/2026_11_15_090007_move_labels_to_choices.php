<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Labels become a team's "ticket_label" choices, so label_ticket now points
 * at choices and the labels table goes. There is no production data yet, so
 * existing labels are not carried over.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('label_ticket')->delete();
        DB::table('audit_logs')->where('auditable_type', 'label')->delete();

        Schema::table('label_ticket', function (Blueprint $table) {
            $table->dropForeign(['label_id']);
            $table->foreign('label_id')->references('id')->on('choices')->cascadeOnDelete();
        });

        Schema::dropIfExists('labels');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('label_ticket')->delete();
        DB::table('choices')->where('list', 'ticket_label')->delete();

        Schema::create('labels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('color', 16)->default('indigo');
            $table->timestamps();

            $table->unique(['team_id', 'name']);
        });

        Schema::table('label_ticket', function (Blueprint $table) {
            $table->dropForeign(['label_id']);
            $table->foreign('label_id')->references('id')->on('labels')->cascadeOnDelete();
        });
    }
};
