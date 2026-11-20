<?php

declare(strict_types=1);

namespace App\ArticleVersion;

use App\Shared\Traits\Enum;

enum ArticleVersionRelationship: string
{
    use Enum;

    case EditedBy = 'editedBy';
}
