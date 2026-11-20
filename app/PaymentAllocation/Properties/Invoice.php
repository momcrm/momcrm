<?php

declare(strict_types=1);

namespace App\PaymentAllocation\Properties;

use App\Invoice\Invoice as InvoiceData;
use App\Shared\Abstracts\AbstractRelatedData;

/**
 * The invoice paid, when loaded.
 *
 * @extends AbstractRelatedData<InvoiceData>
 */
final class Invoice extends AbstractRelatedData
{
    public static function getName(): string
    {
        return 'invoice';
    }

    public static function dataClass(): string
    {
        return InvoiceData::class;
    }
}
