<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a team prints on its invoices: the business name it bills as, its tax
 * ID under whatever name its country uses (ABN, VAT, GST No.), how to reach
 * it, and how to pay.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('billing_name')->nullable()->after('name');
            $table->string('tax_id_label', 20)->nullable()->after('billing_name');
            $table->string('tax_id', 50)->nullable()->after('tax_id_label');
            $table->string('billing_email')->nullable()->after('tax_id');
            $table->string('billing_phone', 50)->nullable()->after('billing_email');
            $table->text('billing_address')->nullable()->after('billing_phone');
            $table->text('payment_details')->nullable()->after('billing_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['billing_name', 'tax_id_label', 'tax_id', 'billing_email', 'billing_phone', 'billing_address', 'payment_details']);
        });
    }
};
