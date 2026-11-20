<?php

declare(strict_types=1);

namespace App\InvoiceItem\Properties;

use App\Shared\Abstracts\AbstractUuid;

/**
 * The one-off charge the line bills, if any.
 */
final class ChargeId extends AbstractUuid
{
    public static function getName(): string
    {
        return 'charge_id';
    }
}
