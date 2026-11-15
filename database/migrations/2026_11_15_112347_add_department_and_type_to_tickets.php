<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a ticket have a type and a department from the team's choices (lists
 * `ticket_type` and `ticket_department`). Both are optional.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignUuid('type_id')->nullable()->after('priority_id')->constrained('choices')->nullOnDelete();
            $table->foreignUuid('department_id')->nullable()->after('type_id')->constrained('choices')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('type_id');
        });
    }
};
