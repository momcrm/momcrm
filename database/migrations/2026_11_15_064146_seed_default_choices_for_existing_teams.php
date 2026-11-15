<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Gives teams that existed before choices the ticket statuses and priorities
 * new teams start with (App\Choice\ChoiceListEnum::defaults()), matching the
 * fixed values tickets use today. Written out here so later changes to the
 * defaults don't change what this migration did.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaults = [
            'ticket_priority' => [
                ['Urgent', 'rose', 'chevrons-up', null, false],
                ['High', 'amber', 'chevron-up', null, false],
                ['Normal', 'slate', 'equal', null, true],
                ['Low', 'slate', 'chevron-down', null, false],
            ],
            'ticket_status' => [
                ['New', 'slate', null, 'open', true],
                ['In progress', 'indigo', null, 'open', false],
                ['Waiting on customer', 'amber', null, 'open', false],
                ['Waiting on supplier', 'blue', null, 'open', false],
                ['Closed', 'green', null, 'closed', true],
            ],
        ];
        $now = now();

        DB::table('teams')
            ->whereNotExists(fn (Builder $query) => $query->selectRaw('1')->from('choices')->whereColumn('choices.team_id', 'teams.id'))
            ->orderBy('id')
            ->each(function (object $team) use ($defaults, $now): void {
                $rows = [];

                foreach ($defaults as $list => $choices) {
                    foreach ($choices as $position => [$name, $color, $icon, $category, $isDefault]) {
                        $rows[] = [
                            'id' => Str::uuid7()->toString(),
                            'team_id' => $team->id,
                            'list' => $list,
                            'name' => $name,
                            'color' => $color,
                            'icon' => $icon,
                            'category' => $category,
                            'position' => $position,
                            'is_default' => $isDefault,
                            'archived_at' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                DB::table('choices')->insert($rows);
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
