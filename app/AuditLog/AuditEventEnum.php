<?php

declare(strict_types=1);

namespace App\AuditLog;

use App\Shared\Traits\Enum;

enum AuditEventEnum: string
{
    use Enum;

    case Created = 'created';
    case Deleted = 'deleted';
    case Updated = 'updated';
    case Viewed = 'viewed';
}
