<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Folders that sort a customer's files, nested under one another. A file
 * may sit in one folder, and has a description.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('folders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignUuid('parent_id')->nullable()->constrained('folders')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->index(['customer_id', 'parent_id']);
        });

        Schema::table('attachments', function (Blueprint $table) {
            $table->foreignUuid('folder_id')->nullable()->after('attachable_id')->constrained('folders')->nullOnDelete();
            $table->text('description')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('folder_id');
            $table->dropColumn('description');
        });

        Schema::dropIfExists('folders');
    }
};
