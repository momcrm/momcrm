<?php

declare(strict_types=1);

namespace App\BankAccount\Actions;

use App\BankAccount\BankAccount;
use App\BankAccount\BankAccountCollection;
use App\BankAccount\BankAccountEloquentModel;
use App\BankAccount\Properties\Name;
use App\BankAccount\Properties\TeamId;

final class FindBankAccount
{
    /**
     * A team's bank accounts by name.
     */
    public static function listForTeam(string $teamId): BankAccountCollection
    {
        return BankAccount::collect(
            BankAccountEloquentModel::query()
                ->withoutGlobalScopes()
                ->where(TeamId::getDatabaseTableColumnName(), $teamId)
                ->orderBy(Name::getDatabaseTableColumnName())
                ->get()
                ->all(),
            'fromEloquentModel',
        );
    }

    /**
     * One of a team's bank accounts by id; empty if it isn't the team's.
     */
    public static function byIdForTeam(?string $id, ?string $teamId): BankAccount
    {
        if ($id === null || $teamId === null) {
            return BankAccount::new();
        }

        $model = BankAccountEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->find($id);

        return $model === null ? BankAccount::new() : BankAccount::fromEloquentModel($model);
    }
}
