<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (['customers', 'contacts', 'projects'] as $records) {
            Schema::table($records, function (Blueprint $table) {
                $table->json('custom_fields')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['customers', 'contacts', 'projects'] as $records) {
            Schema::table($records, function (Blueprint $table) {
                $table->dropColumn('custom_fields');
            });
        }
    }
};
