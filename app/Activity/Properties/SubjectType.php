<?php

declare(strict_types=1);

namespace App\Activity\Properties;

use App\Shared\Abstracts\AbstractMorphMapKeyEnum;

final class SubjectType extends AbstractMorphMapKeyEnum
{
    public static function getName(): string
    {
        return 'subject_type';
    }
}
