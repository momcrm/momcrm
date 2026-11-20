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
        Schema::table('articles', function (Blueprint $table) {
            $table->unsignedInteger('version_major')->default(0)->after('classification');
            $table->unsignedInteger('version_minor')->default(0)->after('version_major');
            $table->string('change_note', 500)->nullable()->after('version_minor');
            $table->foreignUuid('edited_by_id')->nullable()->after('author_id')->constrained('users')->nullOnDelete();
        });

        Schema::create('article_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignUuid('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignUuid('edited_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version_major');
            $table->unsignedInteger('version_minor');
            $table->string('title');
            $table->string('summary', 500)->nullable();
            $table->longText('body')->nullable();
            $table->longText('body_text')->nullable();
            $table->string('classification', 30);
            $table->string('change_note', 500)->nullable();
            $table->timestamp('saved_at')->nullable();
            $table->timestamps();

            $table->index(['article_id', 'version_major', 'version_minor']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_versions');

        Schema::table('articles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('edited_by_id');
            $table->dropColumn(['version_major', 'version_minor', 'change_note']);
        });
    }
};
