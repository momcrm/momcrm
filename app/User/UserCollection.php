<?php

declare(strict_types=1);

namespace App\User;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, User>
 */
final class UserCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return User::class;
    }
}
