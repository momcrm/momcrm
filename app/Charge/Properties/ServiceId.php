<?php

declare(strict_types=1);

namespace App\Charge\Properties;

use App\Shared\Abstracts\AbstractUuid;

/**
 * The service whose period the charge bills, when it bills one: a line made
 * by Single Service Billing that waits to be combined into an invoice.
 */
final class ServiceId extends AbstractUuid
{
    public static function getName(): string
    {
        return 'service_id';
    }
}
