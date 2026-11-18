<?php

declare(strict_types=1);

namespace App\MailboxMessage\Properties;

use App\Shared\Abstracts\AbstractUuid;

/**
 * The comment this message became, or the comment it carried to the customer.
 */
final class CommentId extends AbstractUuid
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'comment_id';
    }
}
