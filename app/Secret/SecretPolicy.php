<?php

declare(strict_types=1);

namespace App\Secret;

use App\AccessRule\AbstractAccessPolicy;
use App\AccessRule\AccessResourceEnum;

final class SecretPolicy extends AbstractAccessPolicy
{
    protected function resource(): AccessResourceEnum
    {
        return AccessResourceEnum::Secret;
    }
}
