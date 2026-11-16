<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Passwords, keys and other credentials kept for a customer, optionally for
 * one of its assets. The key/value pairs are stored encrypted; the version
 * counts changes to them.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('secrets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignUuid('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->string('title');
            $table->string('description')->nullable();
            $table->text('values');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index(['customer_id', 'title']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('secrets');
    }
};
