<?php

declare(strict_types=1);

namespace App\Charge;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Charge>
 */
final class ChargeCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Charge::class;
    }
}
