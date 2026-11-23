<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When the member was reminded that this shift is still open, so the
     * hourly reminder tells them once.
     */
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->timestamp('reminded_at')->nullable()->after('note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn('reminded_at');
        });
    }
};
