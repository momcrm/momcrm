<?php

declare(strict_types=1);

namespace App\ArticleCategory\Properties;

use App\Shared\Abstracts\AbstractUuid;

final class ParentId extends AbstractUuid
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'parent_id';
    }
}
