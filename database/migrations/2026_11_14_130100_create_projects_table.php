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
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignUuid('lead_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon', 32)->nullable();
            $table->string('color', 16)->default('indigo');
            $table->string('status')->default('planning');
            $table->string('visibility')->default('open');
            $table->date('starts_on')->nullable();
            $table->date('due_on')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->boolean('is_sample')->default(false);
            $table->timestamps();

            $table->unique(['team_id', 'number']);
            $table->index(['team_id', 'archived_at']);
        });

        Schema::create('project_user', function (Blueprint $table) {
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['project_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_user');
        Schema::dropIfExists('projects');
    }
};
