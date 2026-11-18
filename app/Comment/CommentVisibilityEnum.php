<?php

declare(strict_types=1);

namespace App\Comment;

use App\Shared\Traits\Enum;

enum CommentVisibilityEnum: string
{
    use Enum;

    /**
     * A note for the team; the customer never sees it.
     */
    case Internal = 'internal';

    /**
     * Part of the conversation with the customer: something they wrote in,
     * or a reply sent to them.
     */
    case Public = 'public';
}
