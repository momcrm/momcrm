<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Access rules are for staff or for customer contacts, and contacts can be
     * let into the customer portal and given a role there.
     */
    public function up(): void
    {
        Schema::table('access_rules', function (Blueprint $table) {
            $table->string('audience', 16)->default('staff')->after('team_id');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->foreignUuid('contact_role_id')->nullable()->after('position')->constrained('choices')->nullOnDelete();
            $table->boolean('portal_access')->default(false)->after('receives_invoices');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contact_role_id');
            $table->dropColumn('portal_access');
        });

        Schema::table('access_rules', function (Blueprint $table) {
            $table->dropColumn('audience');
        });
    }
};
