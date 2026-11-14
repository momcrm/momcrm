<?php

declare(strict_types=1);

namespace App\Comment;

use App\Shared\Traits\Enum;

enum CommentRelationship: string
{
    use Enum;

    case Author = 'author';
    case Ticket = 'ticket';
}
