<?php

declare(strict_types=1);

namespace App\Payment;

use App\AccessRule\AbstractAccessPolicy;
use App\AccessRule\AccessResourceEnum;

final class PaymentPolicy extends AbstractAccessPolicy
{
    protected function resource(): AccessResourceEnum
    {
        return AccessResourceEnum::Payment;
    }
}
