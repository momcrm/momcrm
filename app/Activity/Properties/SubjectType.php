<?php

declare(strict_types=1);

namespace App\Activity\Properties;

use Mom\Data\AbstractString;

final class SubjectType extends AbstractString
{
    public static function getName(): string
    {
        return 'subject_type';
    }
}
