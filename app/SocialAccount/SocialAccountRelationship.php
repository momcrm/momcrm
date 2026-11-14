<?php

declare(strict_types=1);

namespace App\SocialAccount;

use App\Shared\Traits\Enum;

enum SocialAccountRelationship: string
{
    use Enum;

    case User = 'user';
}
