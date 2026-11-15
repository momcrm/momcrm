<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A ticket belongs to one of the team's groups, the people who handle it,
 * instead of a department picked from a list of choices.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->foreignUuid('group_id')->nullable()->after('type_id')->constrained('groups')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
            $table->foreignUuid('department_id')->nullable()->after('type_id')->constrained('choices')->nullOnDelete();
        });
    }
};
