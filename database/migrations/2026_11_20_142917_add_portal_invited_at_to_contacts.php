<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When a contact was last invited to the customer portal.
     */
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->timestamp('portal_invited_at')->nullable()->after('portal_access');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('portal_invited_at');
        });
    }
};
