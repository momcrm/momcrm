<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An email as its sender laid it out, beside its text, for mail that came
 * with HTML. The ticket shows it on request as the original email.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mailbox_messages', function (Blueprint $table) {
            $table->longText('body_html')->nullable()->after('body_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mailbox_messages', function (Blueprint $table) {
            $table->dropColumn('body_html');
        });
    }
};
