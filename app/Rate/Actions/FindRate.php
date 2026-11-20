<?php

declare(strict_types=1);

namespace App\Rate\Actions;

use App\Customer\Actions\FindCustomer;
use App\Rate\Properties\ArchivedAt;
use App\Rate\Properties\Name;
use App\Rate\Properties\TeamId;
use App\Rate\Rate;
use App\Rate\RateCollection;
use App\Rate\RateEloquentModel;
use App\Service\Properties\ArchivedAt as ServiceArchivedAt;
use App\Service\Properties\CustomerId as ServiceCustomerId;
use App\Service\Properties\RateId as ServiceRateId;
use App\Service\ServiceEloquentModel;
use App\Shared\CurrencyEnum;
use App\Shared\Types;
use Illuminate\Database\Eloquent\Builder;

final class FindRate
{
    /**
     * A team's rates by name. Archived rates are left out unless asked for,
     * since they are only kept for the services still on them.
     */
    public static function listForTeam(string $teamId, bool $withArchived = false): RateCollection
    {
        return Rate::collect(
            RateEloquentModel::query()
                ->withoutGlobalScopes()
                ->where(TeamId::getDatabaseTableColumnName(), $teamId)
                ->when(! $withArchived, fn (Builder $query) => $query->whereNull(ArchivedAt::getDatabaseTableColumnName()))
                ->orderBy(Name::getDatabaseTableColumnName())
                ->get()
                ->all(),
            'fromEloquentModel',
        );
    }

    /**
     * Rates by id, keyed by id, whichever team they belong to; for showing the
     * rates of services already read for a team.
     *
     * @param  list<string>  $ids
     * @return array<string, Rate>
     */
    public static function byIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return RateEloquentModel::query()
            ->withoutGlobalScopes()
            ->whereKey($ids)
            ->get()
            ->mapWithKeys(fn (RateEloquentModel $model): array => [$model->id => Rate::fromEloquentModel($model)])
            ->all();
    }

    /**
     * One of a team's rates by id, archived or not; empty if it isn't the team's.
     */
    public static function byIdForTeam(?string $id, string $teamId): Rate
    {
        if ($id === null) {
            return Rate::new();
        }

        $model = RateEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->find($id);

        return $model === null ? Rate::new() : Rate::fromEloquentModel($model);
    }

    /**
     * The currencies the customers of a rate's services are billed in, not
     * counting archived services: the prices the rate can't do without.
     *
     * @return list<CurrencyEnum>
     */
    public static function currenciesInUse(string $rateId): array
    {
        $customerIds = Types::stringList(ServiceEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(ServiceRateId::getDatabaseTableColumnName(), $rateId)
            ->whereNull(ServiceArchivedAt::getDatabaseTableColumnName())
            ->distinct()
            ->pluck(ServiceCustomerId::getDatabaseTableColumnName())
            ->all());

        return array_values(array_unique(FindCustomer::currenciesByIds($customerIds), SORT_REGULAR));
    }
}
