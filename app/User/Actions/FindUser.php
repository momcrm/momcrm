<?php

declare(strict_types=1);

namespace App\User\Actions;

use App\User\Properties\Id;
use App\User\User;
use App\User\UserEloquentModel;

final class FindUser
{
    public static function byId(?string $id): User
    {
        if ($id === null) {
            return User::new();
        }

        return User::fromEloquentModel(
            UserEloquentModel::query()->where(Id::getDatabaseTableColumnName(), $id)->first(),
        );
    }
}
