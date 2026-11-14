<?php

declare(strict_types=1);

namespace App\AuditLog;

use App\Shared\Traits\Enum;

enum AuditLogRelationship: string
{
    use Enum;

    case Actor = 'actor';
}
