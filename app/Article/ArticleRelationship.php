<?php

declare(strict_types=1);

namespace App\Article;

use App\Shared\Traits\Enum;

enum ArticleRelationship: string
{
    use Enum;

    case Author = 'author';
    case Category = 'category';
    case Customer = 'customer';
    case Labels = 'labels';
    case EditedBy = 'editedBy';
}
