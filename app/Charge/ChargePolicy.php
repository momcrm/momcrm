<?php

declare(strict_types=1);

namespace App\Charge;

use App\AccessRule\AbstractAccessPolicy;
use App\AccessRule\AccessResourceEnum;

final class ChargePolicy extends AbstractAccessPolicy
{
    protected function resource(): AccessResourceEnum
    {
        return AccessResourceEnum::Charge;
    }
}
