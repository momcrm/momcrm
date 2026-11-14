<?php

declare(strict_types=1);

namespace App\Favorite\Properties;

use App\Shared\Abstracts\AbstractMorphMapKeyEnum;

final class FavoritableType extends AbstractMorphMapKeyEnum
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'favoritable_type';
    }
}
