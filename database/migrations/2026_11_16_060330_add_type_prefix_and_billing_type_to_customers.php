<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gives a customer a type from the team's choices (list `customer_type`), a
 * short prefix the team files it under, and how it is billed.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignUuid('type_id')->nullable()->after('name')->constrained('choices')->nullOnDelete();
            $table->string('prefix', 8)->nullable()->after('type_id');
            $table->string('billing_type')->default('manual')->after('status');

            $table->unique(['team_id', 'prefix']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['team_id', 'prefix']);
            $table->dropColumn(['prefix', 'billing_type']);
            $table->dropConstrainedForeignId('type_id');
        });
    }
};
