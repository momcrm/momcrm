<?php

declare(strict_types=1);

namespace App\ArticleLink\Properties;

use App\Shared\Abstracts\AbstractUuid;

final class CreatedById extends AbstractUuid
{
    public static bool $includeInDatabaseUpdate = false;

    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'created_by_id';
    }
}
