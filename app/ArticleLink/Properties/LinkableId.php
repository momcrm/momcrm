<?php

declare(strict_types=1);

namespace App\ArticleLink\Properties;

use App\Shared\Abstracts\AbstractUuid;

final class LinkableId extends AbstractUuid
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'linkable_id';
    }
}
