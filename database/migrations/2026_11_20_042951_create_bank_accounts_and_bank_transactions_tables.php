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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('name');
            $table->string('kind')->default('statement');
            $table->char('currency', 3)->default('AUD');
            $table->json('csv_columns')->nullable();
            $table->timestamp('last_imported_at')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'name']);
        });

        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignUuid('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->date('posted_on');
            $table->unsignedBigInteger('amount_in_cents');
            $table->text('description');
            $table->string('external_id', 191);
            $table->string('crn', 30)->nullable();
            $table->string('payer_name')->nullable();
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('matched_by', 20)->nullable();
            $table->timestamp('hidden_at')->nullable();
            $table->timestamps();

            $table->unique(['bank_account_id', 'external_id']);
            $table->index(['team_id', 'posted_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('bank_accounts');
    }
};
