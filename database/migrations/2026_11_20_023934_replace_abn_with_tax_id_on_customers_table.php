<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A customer's tax ID, named the way their country does (ABN, VAT,
     * GST No.), like the team's, instead of an Australian ABN only.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->renameColumn('abn', 'tax_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('tax_id', 50)->nullable()->change();
            $table->string('tax_id_label', 20)->nullable()->after('tax_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('tax_id_label');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->renameColumn('tax_id', 'abn');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('abn', 20)->nullable()->change();
        });
    }
};
