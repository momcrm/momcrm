<?php

declare(strict_types=1);

namespace App\ArticleLink\Properties;

use Mom\Data\AbstractDate;

final class UpdatedAt extends AbstractDate
{
    public static bool $includeInDatabaseCreate = false;

    public static bool $includeInDatabaseUpdate = false;

    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'updated_at';
    }
}
