<?php

declare(strict_types=1);

namespace App\Contract;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

/**
 * Where a contract is in its life. Set by hand: a contract past its end date
 * is not marked expired automatically.
 */
enum ContractStatusEnum: string
{
    use Enum;
    use EnumOptions;

    case Draft = 'draft';
    case Pending = 'pending';
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Awaiting signature',
            self::Active => 'Active',
            self::Expired => 'Expired',
            self::Cancelled => 'Cancelled',
        };
    }
}
