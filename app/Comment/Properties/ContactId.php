<?php

declare(strict_types=1);

namespace App\Comment\Properties;

use App\Shared\Abstracts\AbstractUuid;

/**
 * The customer's contact who wrote it in the customer portal.
 */
final class ContactId extends AbstractUuid
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'contact_id';
    }
}
