<?php

declare(strict_types=1);

namespace App\InvoiceItem\Properties;

use App\Shared\Abstracts\AbstractUuid;

final class ServiceId extends AbstractUuid
{
    public static function getName(): string
    {
        return 'service_id';
    }
}
