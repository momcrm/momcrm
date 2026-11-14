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
        Schema::table('tickets', function (Blueprint $table) {
            $table->uuid('customer_id')->nullable()->change();
            $table->foreignUuid('project_id')->nullable()->after('customer_id')->constrained('projects')->nullOnDelete();
            $table->foreignUuid('parent_id')->nullable()->after('project_id')->constrained('tickets')->cascadeOnDelete();
            $table->date('starts_on')->nullable()->after('priority');
            $table->date('due_on')->nullable()->after('starts_on');
            $table->unsignedInteger('estimate_minutes')->nullable()->after('due_on');
            $table->unsignedInteger('position')->default(0)->after('estimate_minutes');
            $table->timestamp('archived_at')->nullable()->after('closed_at');

            $table->index(['team_id', 'due_on']);
            $table->index(['team_id', 'archived_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['team_id', 'archived_at']);
            $table->dropIndex(['team_id', 'due_on']);
            $table->dropConstrainedForeignId('parent_id');
            $table->dropConstrainedForeignId('project_id');
            $table->dropColumn(['starts_on', 'due_on', 'estimate_minutes', 'position', 'archived_at']);
            $table->uuid('customer_id')->nullable(false)->change();
        });
    }
};
