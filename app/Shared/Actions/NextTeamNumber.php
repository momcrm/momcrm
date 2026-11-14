<?php

declare(strict_types=1);

namespace App\Shared\Actions;

use App\Shared\Table;
use Illuminate\Support\Facades\DB;

/**
 * The next sequential number for a team's records in a table (customers, tickets,
 * invoices), so each team counts from 1.
 *
 * Call it inside the transaction that inserts the row: the existing rows are
 * locked until it commits, and a unique (team_id, number) index guards the rest.
 */
final class NextTeamNumber
{
    public static function for(Table $table, string $teamId): int
    {
        $numbers = DB::table($table->value)
            ->where('team_id', $teamId)
            ->lockForUpdate()
            ->pluck('number');

        return (int) $numbers->max() + 1;
    }
}
