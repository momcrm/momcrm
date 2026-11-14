<?php

declare(strict_types=1);

namespace App\Favorite;

use App\Shared\Traits\Enum;

enum FavoriteRelationship: string
{
    use Enum;

    case Favoritable = 'favoritable';
    case User = 'user';
}
