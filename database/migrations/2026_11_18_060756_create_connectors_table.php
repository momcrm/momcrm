<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The outside accounts a team has connected, such as a Microsoft 365 sign-in.
 * Features like email reach the outside service through one of these. The
 * credentials are stored encrypted; settings hold nothing secret.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('connectors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('type');
            $table->string('name');
            $table->string('account');
            $table->string('status')->default('connected');
            $table->text('credentials');
            $table->json('settings')->nullable();
            $table->text('last_error')->nullable();
            $table->foreignUuid('connected_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['team_id', 'type', 'account']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('connectors');
    }
};
