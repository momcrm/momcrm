<?php

declare(strict_types=1);

namespace App\Favorite;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Favorite>
 */
final class FavoriteCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Favorite::class;
    }
}
