<?php

declare(strict_types=1);

namespace App\AuditLog;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, AuditLog>
 */
final class AuditLogCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return AuditLog::class;
    }
}
