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
                        'name' => 'Owners and admins can do everything',
                        'resources' => '[]',
                        'actions' => '[]',
                        'effect' => 'allow',
                        'conditions' => json_encode([['attribute' => 'subject.team_role', 'operator' => 'in', 'value' => ['owner', 'admin']]]),
                        'is_enabled' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'id' => Str::uuid7()->toString(),
                        'team_id' => $team->id,
                        'name' => 'Everyone can create and edit',
                        'resources' => '[]',
                        'actions' => json_encode(['change_status', 'close', 'create', 'import', 'move', 'reopen', 'update']),
                        'effect' => 'allow',
                        'conditions' => '[]',
                        'is_enabled' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'id' => Str::uuid7()->toString(),
                        'team_id' => $team->id,
                        'name' => 'Everyone can delete comments and files',
                        'resources' => json_encode(['attachment', 'comment']),
                        'actions' => json_encode(['delete']),
                        'effect' => 'allow',
                        'conditions' => '[]',
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
