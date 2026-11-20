<?php

declare(strict_types=1);

namespace App\Attachment\Properties;

use App\Shared\Abstracts\AbstractUuid;

/**
 * The customer's contact who added the file in the customer portal.
 */
final class UploadedByContactId extends AbstractUuid
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'uploaded_by_contact_id';
    }
}
