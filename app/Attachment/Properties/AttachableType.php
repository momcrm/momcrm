<?php

declare(strict_types=1);

namespace App\Attachment\Properties;

use App\Shared\Abstracts\AbstractMorphMapKeyEnum;

final class AttachableType extends AbstractMorphMapKeyEnum
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'attachable_type';
    }
}
