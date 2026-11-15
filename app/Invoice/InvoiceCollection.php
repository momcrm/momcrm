<?php

declare(strict_types=1);

namespace App\Invoice;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Invoice>
 */
final class InvoiceCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Invoice::class;
    }
}
