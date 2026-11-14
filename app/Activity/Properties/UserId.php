<?php

declare(strict_types=1);

namespace App\Activity\Properties;

use App\Shared\Abstracts\AbstractUuid;

final class UserId extends AbstractUuid
{
    public static function getName(): string
    {
        return 'user_id';
    }
}
