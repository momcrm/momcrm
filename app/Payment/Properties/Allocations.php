<?php

declare(strict_types=1);

namespace App\Payment\Properties;

use App\PaymentAllocation\PaymentAllocationCollection;
use App\Shared\Abstracts\AbstractRelatedDataCollection;

/**
 * The invoices the payment was applied to, when loaded.
 */
final class Allocations extends AbstractRelatedDataCollection
{
    public static function getName(): string
    {
        return 'allocations';
    }

    public static function collectionClass(): string
    {
        return PaymentAllocationCollection::class;
    }

    public function toDataCollection(): PaymentAllocationCollection
    {
        $collection = parent::toDataCollection();

        return $collection instanceof PaymentAllocationCollection ? $collection : new PaymentAllocationCollection;
    }
}
