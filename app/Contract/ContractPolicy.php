<?php

declare(strict_types=1);

namespace App\Contract;

use App\AccessRule\AbstractAccessPolicy;
use App\AccessRule\AccessResourceEnum;

final class ContractPolicy extends AbstractAccessPolicy
{
    protected function resource(): AccessResourceEnum
    {
        return AccessResourceEnum::Contract;
    }
}
