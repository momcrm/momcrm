<?php

declare(strict_types=1);

namespace App\Attachment\Properties;

use App\Shared\Abstracts\AbstractUuid;

final class UploadedById extends AbstractUuid
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'uploaded_by_id';
    }
}
