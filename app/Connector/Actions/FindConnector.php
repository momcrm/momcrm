<?php

declare(strict_types=1);

namespace App\Connector\Actions;

use App\Connector\Connector;
use App\Connector\ConnectorCollection;
use App\Connector\ConnectorEloquentModel;
use App\Connector\Properties\Id;
use App\Connector\Properties\Name;
use App\Connector\Properties\TeamId;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Database\Eloquent\Builder;

final class FindConnector
{
    /**
     * Every account the team has connected, by name.
     */
    public static function allForTeam(string $teamId): ConnectorCollection
    {
        return Connector::collect(
            self::query()
                ->where(TeamId::getDatabaseTableColumnName(), $teamId)
                ->orderBy(Name::getDatabaseTableColumnName())
                ->orderBy(Id::getDatabaseTableColumnName())
                ->get()
                ->all(),
        );
    }

    public static function byIdForTeam(?string $id, ?string $teamId): Connector
    {
        if ($id === null || $teamId === null) {
            return Connector::new();
        }

        return Connector::fromEloquentModel(
            self::query()
                ->where(TeamId::getDatabaseTableColumnName(), $teamId)
                ->where(Id::getDatabaseTableColumnName(), $id)
                ->first(),
        );
    }

    /**
     * A connector in any team, for the jobs that use connectors with nobody signed in.
     */
    public static function byId(?string $id): Connector
    {
        if ($id === null) {
            return Connector::new();
        }

        return Connector::fromEloquentModel(self::query()->where(Id::getDatabaseTableColumnName(), $id)->first());
    }

    /**
     * @return Builder<ConnectorEloquentModel>
     */
    private static function query(): Builder
    {
        return ConnectorEloquentModel::query()->withoutGlobalScope(CurrentTeamScope::class);
    }
}
