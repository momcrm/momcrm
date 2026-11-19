<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Services are priced from a rate and billed from a start date, as Amphora's
 * company services are. Each has a short name unique to its customer, an
 * optional contact, and can be turned off (inactive) or archived.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->foreignUuid('rate_id')->nullable()->after('location_id')->constrained('rates')->nullOnDelete();
            $table->foreignUuid('contact_id')->nullable()->after('rate_id')->constrained('contacts')->nullOnDelete();
            $table->string('short_name')->nullable()->after('name');
            $table->boolean('auto_renew')->default(false)->after('status');
            $table->timestamp('archived_at')->nullable()->after('auto_renew');
            $table->unsignedTinyInteger('billing_period_in_months')->nullable()->default(null)->change();
            $table->renameColumn('starts_on', 'billing_starts_on');
            $table->dropColumn('unit_price_in_cents');

            $table->unique(['customer_id', 'short_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->unsignedInteger('unit_price_in_cents')->nullable()->after('quantity');
            $table->renameColumn('billing_starts_on', 'starts_on');
            $table->unsignedInteger('billing_period_in_months')->nullable(false)->default(1)->change();
        });

        // The unique index starts with customer_id, a foreign key, so MySQL
        // needs the foreign key's own index back before it lets this one go.
        Schema::table('services', function (Blueprint $table) {
            $table->index('customer_id');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropUnique(['customer_id', 'short_name']);
            $table->dropConstrainedForeignId('contact_id');
            $table->dropConstrainedForeignId('rate_id');
            $table->dropColumn(['short_name', 'auto_renew', 'archived_at']);
        });
    }
};
