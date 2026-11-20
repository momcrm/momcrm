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
        Schema::table('charges', function (Blueprint $table) {
            $table->foreignUuid('service_id')->nullable()->after('product_id')->constrained('services')->cascadeOnDelete();
            $table->date('rental_starts_on')->nullable()->after('charged_on');
            $table->date('rental_ends_on')->nullable()->after('rental_starts_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_id');
            $table->dropColumn(['rental_starts_on', 'rental_ends_on']);
        });
    }
};
