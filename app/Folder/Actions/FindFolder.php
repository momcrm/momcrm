<?php

declare(strict_types=1);

namespace App\Folder\Actions;

use App\Folder\Folder;
use App\Folder\FolderCollection;
use App\Folder\FolderEloquentModel;
use App\Folder\Properties\CustomerId;
use App\Folder\Properties\Id;
use App\Folder\Properties\Name;
use App\Folder\Properties\TeamId;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Database\Eloquent\Builder;

final class FindFolder
{
    public static function byIdForTeam(?string $id, ?string $teamId): Folder
    {
        if ($id === null || $teamId === null) {
            return Folder::new();
        }

        return Folder::fromEloquentModel(self::query($teamId)->where(Id::getDatabaseTableColumnName(), $id)->first());
    }

    /**
     * Every folder of a customer, at every level, by name.
     */
    public static function forCustomer(string $customerId, string $teamId): FolderCollection
    {
        return Folder::collect(self::query($teamId)
            ->where(CustomerId::getDatabaseTableColumnName(), $customerId)
            ->orderBy(Name::getDatabaseTableColumnName())
            ->get()
            ->all(), 'fromEloquentModel');
    }

    /**
     * The ids of a folder and every folder below it, at any depth.
     *
     * @return array<int, string>
     */
    public static function withDescendantIds(Folder $folder): array
    {
        $all = self::forCustomer($folder->getCustomerId()->toString(), $folder->getTeamId()->toString());
        $ids = [$folder->getId()->toString()];

        for ($index = 0; $index < count($ids); $index++) {
            foreach ($all as $candidate) {
                if ($candidate->getParentId()->toNullableString() === $ids[$index]) {
                    $ids[] = $candidate->getId()->toString();
                }
            }
        }

        return $ids;
    }

    /**
     * @return Builder<FolderEloquentModel>
     */
    private static function query(string $teamId): Builder
    {
        return FolderEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId);
    }
}
