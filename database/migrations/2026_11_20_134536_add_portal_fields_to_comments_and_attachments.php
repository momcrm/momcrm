<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contacts write on tickets and add files from the customer portal, and
     * staff choose which of a ticket's files the customer can see there.
     */
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->foreignUuid('contact_id')->nullable()->after('user_id')->constrained('contacts')->nullOnDelete();
        });

        Schema::table('attachments', function (Blueprint $table) {
            $table->foreignUuid('uploaded_by_contact_id')->nullable()->after('uploaded_by_id')->constrained('contacts')->nullOnDelete();
            $table->boolean('shared_with_customer')->default(false)->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('uploaded_by_contact_id');
            $table->dropColumn('shared_with_customer');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contact_id');
        });
    }
};
