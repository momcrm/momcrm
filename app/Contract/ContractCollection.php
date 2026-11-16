<?php

declare(strict_types=1);

namespace App\Contract;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Contract>
 */
final class ContractCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Contract::class;
    }
}
