<?php

declare(strict_types=1);

namespace App\BankRule\Actions;

use App\BankRule\BankRule;
use App\BankRule\BankRuleCollection;
use App\BankRule\BankRuleEloquentModel;
use App\BankRule\BankRuleRelationship;
use App\BankRule\Properties\Contains;
use App\BankRule\Properties\Kind;
use App\BankRule\Properties\TeamId;

final class FindBankRule
{
    /**
     * A team's rules, match rules first, each by its text, with the customer
     * a match rule names.
     */
    public static function listForTeam(string $teamId): BankRuleCollection
    {
        return BankRule::collect(
            BankRuleEloquentModel::query()
                ->withoutGlobalScopes()
                ->with(BankRuleRelationship::Customer->value)
                ->where(TeamId::getDatabaseTableColumnName(), $teamId)
                ->orderByDesc(Kind::getDatabaseTableColumnName())
                ->orderBy(Contains::getDatabaseTableColumnName())
                ->get()
                ->all(),
            'fromEloquentModel',
        );
    }

    public static function byIdForTeam(?string $id, ?string $teamId): BankRule
    {
        if ($id === null || $teamId === null) {
            return BankRule::new();
        }

        $model = BankRuleEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->find($id);

        return $model === null ? BankRule::new() : BankRule::fromEloquentModel($model);
    }
}
