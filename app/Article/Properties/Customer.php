<?php

declare(strict_types=1);

namespace App\Article\Properties;

use App\Customer\Customer as CustomerData;
use App\Shared\Abstracts\AbstractRelatedData;

/**
 * The customer the article is about, when loaded.
 *
 * @extends AbstractRelatedData<CustomerData>
 */
final class Customer extends AbstractRelatedData
{
    public static function getName(): string
    {
        return 'customer';
    }

    public static function dataClass(): string
    {
        return CustomerData::class;
    }
}
