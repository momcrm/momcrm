<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prices a rate in one or more currencies, as a JSON list of
 * {currency, price_in_cents, cost_in_cents}, and gives each customer the
 * currency they are billed in and each invoice the currency it is in.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rates', function (Blueprint $table) {
            $table->json('prices')->nullable()->after('description');
        });

        Schema::table('rates', function (Blueprint $table) {
            $table->dropColumn(['price_in_cents', 'cost_in_cents']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->char('currency', 3)->default('AUD')->after('billing_type');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->char('currency', 3)->default('AUD')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('currency');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('currency');
        });

        Schema::table('rates', function (Blueprint $table) {
            $table->unsignedInteger('price_in_cents')->default(0)->after('description');
            $table->unsignedInteger('cost_in_cents')->nullable()->after('price_in_cents');
        });

        Schema::table('rates', function (Blueprint $table) {
            $table->dropColumn('prices');
        });
    }
};
