<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets projects and assets have the team's labels too, which become one list
 * (`label`) shared by tickets, projects and assets.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('label_project', function (Blueprint $table) {
            $table->foreignUuid('label_id')->constrained('choices')->cascadeOnDelete();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->primary(['label_id', 'project_id']);
            $table->index('project_id');
        });

        Schema::create('label_asset', function (Blueprint $table) {
            $table->foreignUuid('label_id')->constrained('choices')->cascadeOnDelete();
            $table->foreignUuid('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->primary(['label_id', 'asset_id']);
            $table->index('asset_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('label_asset');
        Schema::dropIfExists('label_project');
    }
};
