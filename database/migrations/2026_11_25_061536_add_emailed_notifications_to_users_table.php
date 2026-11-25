<?php

use App\User\Properties\EmailedNotifications;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which kinds of inbox notification someone is also emailed. Empty until
 * they choose, when the defaults apply.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json(EmailedNotifications::getDatabaseTableColumnName())->nullable()->after('timezone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(EmailedNotifications::getDatabaseTableColumnName());
        });
    }
};
