<?php

declare(strict_types=1);

namespace App\BankTransaction;

use App\AccessRule\AbstractAccessPolicy;
use App\AccessRule\AccessResourceEnum;

final class BankTransactionPolicy extends AbstractAccessPolicy
{
    protected function resource(): AccessResourceEnum
    {
        return AccessResourceEnum::BankTransaction;
    }
}
