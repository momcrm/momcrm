<?php

declare(strict_types=1);

namespace App\Activity\Properties;

use App\Shared\Abstracts\AbstractUuid;

final class SubjectId extends AbstractUuid
{
    public static function getName(): string
    {
        return 'subject_id';
    }
}
