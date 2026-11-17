<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A comment is now on a ticket or on a custom object's record, so the
     * ticket becomes optional.
     */
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->uuid('ticket_id')->nullable()->change();
            $table->foreignUuid('record_id')->nullable()->after('ticket_id')->constrained('records')->cascadeOnDelete();
            $table->index(['record_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations. Comments on records go with the column.
     */
    public function down(): void
    {
        DB::table('comments')->whereNotNull('record_id')->delete();

        // The foreign key goes first: MySQL will not drop an index it uses.
        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['record_id']);
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex(['record_id', 'created_at']);
            $table->dropColumn('record_id');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->uuid('ticket_id')->nullable(false)->change();
        });
    }
};
