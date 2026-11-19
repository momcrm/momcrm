<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The days a line bills a service for, so the next invoice knows where the
 * last one stopped.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->date('rental_starts_on')->nullable()->after('unit_price_in_cents');
            $table->date('rental_ends_on')->nullable()->after('rental_starts_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['rental_starts_on', 'rental_ends_on']);
        });
    }
};
