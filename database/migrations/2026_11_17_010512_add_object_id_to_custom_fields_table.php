<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A field's name is now unique within its custom object rather than its
     * record type, since every object's fields share the `record` type.
     */
    public function up(): void
    {
        Schema::table('custom_fields', function (Blueprint $table) {
            $table->foreignUuid('object_id')->nullable()->after('target')->constrained('custom_objects')->cascadeOnDelete();
        });

        // The new index goes in before the old one comes out: MySQL keeps
        // the team_id foreign key on whichever index starts with team_id.
        Schema::table('custom_fields', function (Blueprint $table) {
            $table->unique(['team_id', 'target', 'object_id', 'name']);
        });

        Schema::table('custom_fields', function (Blueprint $table) {
            $table->dropUnique(['team_id', 'target', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_fields', function (Blueprint $table) {
            $table->unique(['team_id', 'target', 'name']);
        });

        Schema::table('custom_fields', function (Blueprint $table) {
            $table->dropUnique(['team_id', 'target', 'object_id', 'name']);
        });

        Schema::table('custom_fields', function (Blueprint $table) {
            $table->dropConstrainedForeignId('object_id');
        });
    }
};
