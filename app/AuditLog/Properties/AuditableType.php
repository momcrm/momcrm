<?php

declare(strict_types=1);

namespace App\AuditLog\Properties;

use App\Shared\Abstracts\AbstractMorphMapKeyEnum;

final class AuditableType extends AbstractMorphMapKeyEnum
{
    public static function getName(): string
    {
        return 'auditable_type';
    }
}
