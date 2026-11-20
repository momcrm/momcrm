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
        Schema::table('teams', function (Blueprint $table) {
            $table->unsignedSmallInteger('days_to_pay')->default(14)->after('payment_details');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedSmallInteger('days_to_pay')->nullable()->after('billing_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('days_to_pay');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('days_to_pay');
        });
    }
};
