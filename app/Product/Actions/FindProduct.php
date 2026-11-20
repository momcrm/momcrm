<?php

declare(strict_types=1);

namespace App\Product\Actions;

use App\Product\Product;
use App\Product\ProductCollection;
use App\Product\ProductEloquentModel;
use App\Product\Properties\ArchivedAt;
use App\Product\Properties\Name;
use App\Product\Properties\TeamId;
use Illuminate\Database\Eloquent\Builder;

final class FindProduct
{
    /**
     * A team's products by name. Archived ones are left out unless asked for.
     */
    public static function listForTeam(string $teamId, bool $withArchived = false): ProductCollection
    {
        return Product::collect(
            ProductEloquentModel::query()
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
     * One of a team's products by id, archived or not; empty if it isn't the team's.
     */
    public static function byIdForTeam(?string $id, ?string $teamId): Product
    {
        if ($id === null || $teamId === null) {
            return Product::new();
        }

        $model = ProductEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->find($id);

        return $model === null ? Product::new() : Product::fromEloquentModel($model);
    }
}
