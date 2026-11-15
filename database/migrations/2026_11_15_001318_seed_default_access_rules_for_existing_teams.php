<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Gives teams that existed before access rules the rules new teams start with
 * (App\AccessRule\Actions\SeedDefaultAccessRules), written out here so later
 * changes to the defaults don't change what this migration did.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        DB::table('teams')
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('access_rules')->whereColumn('access_rules.team_id', 'teams.id'))
            ->orderBy('id')
            ->each(function (object $team) use ($now): void {
                DB::table('access_rules')->insert([
                    [
                        'id' => Str::uuid7()->toString(),
                        'team_id' => $team->id,
                        'name' => 'Everyone can work on everything',
                        'resources' => '[]',
                        'actions' => '[]',
                        'effect' => 'allow',
                        'conditions' => '[]',
                        'is_enabled' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'id' => Str::uuid7()->toString(),
                        'team_id' => $team->id,
                        'name' => 'Only owners and admins delete, archive and manage access',
                        'resources' => json_encode(['asset', 'contact', 'customer', 'group', 'invoice', 'location', 'milestone', 'project', 'service', 'ticket']),
                        'actions' => json_encode(['archive', 'change_visibility', 'delete', 'manage_members', 'restore']),
                        'effect' => 'deny',
                        'conditions' => json_encode([['attribute' => 'subject.team_role', 'operator' => 'equals', 'value' => 'member']]),
                        'is_enabled' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
