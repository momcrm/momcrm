<?php

declare(strict_types=1);

namespace App\LeaveRequest\Properties;

use App\Shared\Abstracts\AbstractUuid;

final class LeaveTypeId extends AbstractUuid
{
    public static function getName(): string
    {
        return 'leave_type_id';
    }
}
