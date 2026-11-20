<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What a team's invoice numbers start with, e.g. INV- in INV-0042. Each
     * invoice keeps the prefix it was drafted with, so changing it doesn't
     * renumber invoices already sent.
     */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('invoice_prefix', 12)->default('INV-')->after('payment_details');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('prefix', 12)->default('INV-')->after('number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('prefix');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('invoice_prefix');
        });
    }
};
