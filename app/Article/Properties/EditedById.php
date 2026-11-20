<?php

declare(strict_types=1);

namespace App\Article\Properties;

use App\Shared\Abstracts\AbstractUuid;

/**
 * Who saved the article's current version.
 */
final class EditedById extends AbstractUuid
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'edited_by_id';
    }
}
