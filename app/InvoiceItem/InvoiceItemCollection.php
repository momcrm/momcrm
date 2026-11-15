<?php

declare(strict_types=1);

namespace App\InvoiceItem;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, InvoiceItem>
 */
final class InvoiceItemCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return InvoiceItem::class;
    }
}
