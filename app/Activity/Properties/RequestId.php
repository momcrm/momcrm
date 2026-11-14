<?php

declare(strict_types=1);

namespace App\Activity\Properties;

use App\Shared\Abstracts\AbstractUuid;

final class RequestId extends AbstractUuid
{
    public static function getName(): string
    {
        return 'request_id';
    }
}
