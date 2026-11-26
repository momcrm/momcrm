<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A Cloudflare account is now connected by signing in, and that kind of
 * connector takes the name "cloudflare". The accounts connected so far were
 * all connected with an API token, so they move to the name that kind now has.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('connectors')->where('type', 'cloudflare')->update(['type' => 'cloudflare-token']);
    }

    /**
     * Reverse the migrations.
     *
     * Accounts connected by signing in have no token to go back to, so they
     * are removed, and with them their mailboxes; the accounts connected
     * with a token get their old name back.
     */
    public function down(): void
    {
        DB::table('connectors')->where('type', 'cloudflare')->delete();
        DB::table('connectors')->where('type', 'cloudflare-token')->update(['type' => 'cloudflare']);
    }
};
