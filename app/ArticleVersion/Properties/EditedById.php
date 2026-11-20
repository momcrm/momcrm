<?php

declare(strict_types=1);

namespace App\ArticleVersion\Properties;

use App\Shared\Abstracts\AbstractUuid;

final class EditedById extends AbstractUuid
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'edited_by_id';
    }
}
