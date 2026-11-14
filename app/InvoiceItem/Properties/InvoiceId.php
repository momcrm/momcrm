<?php

declare(strict_types=1);

namespace App\InvoiceItem\Properties;

use App\Shared\Abstracts\AbstractUuid;

final class InvoiceId extends AbstractUuid
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'invoice_id';
    }
}
