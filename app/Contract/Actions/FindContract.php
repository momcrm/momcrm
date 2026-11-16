<?php

declare(strict_types=1);

namespace App\Contract\Actions;

use App\Contract\Contract;
use App\Contract\ContractCollection;
use App\Contract\ContractEloquentModel;
use App\Contract\ContractStatusEnum;
use App\Contract\ContractTypeEnum;
use App\Contract\Properties\CustomerId;
use App\Contract\Properties\EndsOn;
use App\Contract\Properties\Id;
use App\Contract\Properties\Status;
use App\Contract\Properties\TeamId;
use App\Contract\Properties\Title;
use App\Contract\Properties\Type;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Database\Eloquent\Builder;

final class FindContract
{
    /**
     * The orders a contract list can be sorted in.
     *
     * @var array<int, string>
     */
    public const array SORTS = ['title', 'starts_on', 'ends_on', 'signed_on'];

    public static function byIdForTeam(?string $id, ?string $teamId): Contract
    {
        if ($id === null || $teamId === null) {
            return Contract::new();
        }

        return Contract::fromEloquentModel(self::query($teamId)->where(Id::getDatabaseTableColumnName(), $id)->first());
    }

    /**
     * A customer's contracts, optionally with one status or type. Sorted by
     * title, or by a date with contracts that have none last.
     */
    public static function forCustomer(
        string $customerId,
        string $teamId,
        ?ContractStatusEnum $status = null,
        ?ContractTypeEnum $type = null,
        string $sort = 'title',
        string $direction = 'asc',
    ): ContractCollection {
        $sort = in_array($sort, self::SORTS, true) ? $sort : Title::getDatabaseTableColumnName();
        $direction = $direction === 'desc' ? 'desc' : 'asc';
        $undatedLast = match ($sort) {
            'starts_on' => 'starts_on is null',
            'ends_on' => 'ends_on is null',
            'signed_on' => 'signed_on is null',
            default => 'title is null',
        };

        return Contract::collect(self::query($teamId)
            ->where(CustomerId::getDatabaseTableColumnName(), $customerId)
            ->when($status, fn (Builder $query, ContractStatusEnum $status) => $query->where(Status::getDatabaseTableColumnName(), $status->value))
            ->when($type, fn (Builder $query, ContractTypeEnum $type) => $query->where(Type::getDatabaseTableColumnName(), $type->value))
            ->orderByRaw($undatedLast)
            ->orderBy($sort, $direction)
            ->orderBy(Title::getDatabaseTableColumnName())
            ->get()
            ->all(), 'fromEloquentModel');
    }

    public static function countForCustomer(string $customerId, string $teamId): int
    {
        return self::query($teamId)->where(CustomerId::getDatabaseTableColumnName(), $customerId)->count();
    }

    /**
     * A customer's active contracts that end between today and the given
     * number of days from now, soonest first.
     */
    public static function endingSoonForCustomer(string $customerId, string $teamId, int $withinDays): ContractCollection
    {
        return Contract::collect(self::query($teamId)
            ->where(CustomerId::getDatabaseTableColumnName(), $customerId)
            ->where(Status::getDatabaseTableColumnName(), ContractStatusEnum::Active->value)
            ->whereBetween(EndsOn::getDatabaseTableColumnName(), [now()->toDateString(), now()->addDays($withinDays)->toDateString()])
            ->orderBy(EndsOn::getDatabaseTableColumnName())
            ->get()
            ->all(), 'fromEloquentModel');
    }

    /**
     * @return Builder<ContractEloquentModel>
     */
    private static function query(string $teamId): Builder
    {
        return ContractEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId);
    }
}
