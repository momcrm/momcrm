<?php

declare(strict_types=1);

namespace App\Invoice\Properties;

use App\InvoiceItem\InvoiceItemCollection;
use App\Shared\Abstracts\AbstractRelatedDataCollection;

/**
 * The invoice's line items, when loaded.
 */
final class Items extends AbstractRelatedDataCollection
{
    public static function getName(): string
    {
        return 'items';
    }

    public static function collectionClass(): string
    {
        return InvoiceItemCollection::class;
    }

    public function toDataCollection(): InvoiceItemCollection
    {
        $collection = parent::toDataCollection();

        return $collection instanceof InvoiceItemCollection ? $collection : new InvoiceItemCollection;
    }
}
