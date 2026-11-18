<?php

use App\User\Properties\IsPlatformAdmin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform admins look after the installation itself, such as its `.env`,
 * rather than any one team. Only `php artisan users:platform-admin` sets it.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean(IsPlatformAdmin::getDatabaseTableColumnName())->default(false)->after('timezone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(IsPlatformAdmin::getDatabaseTableColumnName());
        });
    }
};
