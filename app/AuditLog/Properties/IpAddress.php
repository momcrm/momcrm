<?php

declare(strict_types=1);

namespace App\AuditLog\Properties;

use Mom\Data\AbstractString;

final class IpAddress extends AbstractString
{
    public static function getName(): string
    {
        return 'ip_address';
    }
}
