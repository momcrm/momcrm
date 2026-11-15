<?php

declare(strict_types=1);

namespace App\Customer;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Customer>
 */
final class CustomerCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Customer::class;
    }
}
