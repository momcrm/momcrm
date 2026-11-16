<?php

declare(strict_types=1);

namespace App\Contract;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

/**
 * What kind of agreement a contract is.
 */
enum ContractTypeEnum: string
{
    use Enum;
    use EnumOptions;

    case Msa = 'msa';
    case Nda = 'nda';
    case Sla = 'sla';
    case Sa = 'sa';

    public function label(): string
    {
        return match ($this) {
            self::Msa => 'Master services agreement',
            self::Nda => 'Non-disclosure agreement',
            self::Sla => 'Service level agreement',
            self::Sa => 'Services agreement',
        };
    }
}
