<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('onboarding_use_case')->nullable()->after('current_team_id');
            $table->string('onboarding_role')->nullable()->after('onboarding_use_case');
            $table->string('onboarding_team_size')->nullable()->after('onboarding_role');
            $table->timestamp('onboarded_at')->nullable()->after('onboarding_team_size');
            $table->timestamp('getting_started_dismissed_at')->nullable()->after('onboarded_at');
        });

        // Accounts that existed before onboarding shipped should not be sent through it.
        DB::table('users')->update(['onboarded_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'onboarding_use_case',
                'onboarding_role',
                'onboarding_team_size',
                'onboarded_at',
                'getting_started_dismissed_at',
            ]);
        });
    }
};
