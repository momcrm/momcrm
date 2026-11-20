<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nests rates, as Amphora does: a rate can sit under another, so the price
 * list reads as a tree. A rate's name need only differ from the rates beside
 * it under the same parent, which the form checks, so the team-wide unique
 * index goes. The (team_id, parent_id) index is added first because MySQL
 * won't drop an index the team foreign key is using.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rates', function (Blueprint $table) {
            $table->foreignUuid('parent_id')->nullable()->after('team_id')->constrained('rates')->nullOnDelete();
            $table->index(['team_id', 'parent_id']);
        });

        Schema::table('rates', function (Blueprint $table) {
            $table->dropUnique(['team_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rates', function (Blueprint $table) {
            $table->unique(['team_id', 'name']);
        });

        Schema::table('rates', function (Blueprint $table) {
            $table->dropIndex(['team_id', 'parent_id']);
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
