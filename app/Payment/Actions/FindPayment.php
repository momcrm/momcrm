<?php

declare(strict_types=1);

namespace App\Payment\Actions;

use App\Payment\Payment;
use App\Payment\PaymentEloquentModel;
use App\Payment\PaymentRelationship;
use App\Payment\Properties\AllocatedInCents;
use App\Payment\Properties\Id;
use App\Payment\Properties\TeamId;
use App\PaymentAllocation\Properties\AmountInCents as AllocationAmountInCents;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Database\Eloquent\Builder;

final class FindPayment
{
    public static function byIdForTeam(?string $id, ?string $teamId): Payment
    {
        if ($id === null || $teamId === null) {
            return Payment::new();
        }

        return Payment::fromEloquentModel(self::query($teamId)->where(Id::getDatabaseTableColumnName(), $id)->first());
    }

    /**
     * A team's payments, each with how much of it has been applied to
     * invoices.
     *
     * @return Builder<PaymentEloquentModel>
     */
    private static function query(string $teamId): Builder
    {
        return PaymentEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->withSum([
                PaymentRelationship::Allocations->value.' as '.AllocatedInCents::getDatabaseTableColumnName() => fn (Builder $query) => $query->withoutGlobalScope(CurrentTeamScope::class),
            ], AllocationAmountInCents::getDatabaseTableColumnName());
    }
}
