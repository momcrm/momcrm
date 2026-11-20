<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets articles be filed in a tree of categories, have the team's labels,
 * and be due for review on a day.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('article_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignUuid('parent_id')->nullable()->constrained('article_categories')->nullOnDelete();
            $table->string('name', 100);
            $table->timestamps();

            $table->index(['team_id', 'name']);
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->foreignUuid('category_id')->nullable()->after('edited_by_id')->constrained('article_categories')->nullOnDelete();
            $table->date('last_reviewed_on')->nullable()->after('archived_at');
            $table->date('next_review_on')->nullable()->after('last_reviewed_on');
        });

        Schema::create('label_article', function (Blueprint $table) {
            $table->foreignUuid('label_id')->constrained('choices')->cascadeOnDelete();
            $table->foreignUuid('article_id')->constrained('articles')->cascadeOnDelete();
            $table->primary(['label_id', 'article_id']);
            $table->index('article_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('label_article');

        Schema::table('articles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn(['last_reviewed_on', 'next_review_on']);
        });

        Schema::dropIfExists('article_categories');
    }
};
