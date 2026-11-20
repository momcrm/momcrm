<?php

declare(strict_types=1);

namespace App\Shared;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

/**
 * The currencies a team can price rates and bill customers in, by ISO 4217
 * code. Every one has cents, since amounts are stored in hundredths.
 */
enum CurrencyEnum: string
{
    use Enum;
    use EnumOptions;

    case AUD = 'AUD';
    case CAD = 'CAD';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case HKD = 'HKD';
    case NZD = 'NZD';
    case PHP = 'PHP';
    case SGD = 'SGD';
    case USD = 'USD';

    /**
     * What a new customer is billed in, and every amount from before
     * currencies were added.
     */
    public static function default(): self
    {
        return self::AUD;
    }

    public function label(): string
    {
        return match ($this) {
            self::AUD => 'AUD · Australian dollar',
            self::CAD => 'CAD · Canadian dollar',
            self::EUR => 'EUR · Euro',
            self::GBP => 'GBP · British pound',
            self::HKD => 'HKD · Hong Kong dollar',
            self::NZD => 'NZD · New Zealand dollar',
            self::PHP => 'PHP · Philippine peso',
            self::SGD => 'SGD · Singapore dollar',
            self::USD => 'USD · US dollar',
        };
    }
}
