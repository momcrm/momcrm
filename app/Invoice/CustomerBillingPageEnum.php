<?php

declare(strict_types=1);

namespace App\Invoice;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

/**
 * The pages of a customer's Billing tab, as Amphora's company Billing section
 * has them.
 */
enum CustomerBillingPageEnum: string
{
    use Enum;
    use EnumOptions;

    case Multiple = 'multiple';
    case Single = 'single';
    case Product = 'product';
    case Combine = 'combine';

    public function label(): string
    {
        return match ($this) {
            self::Multiple => 'Multiple Service Billing',
            self::Single => 'Single Service Billing',
            self::Product => 'Product Billing',
            self::Combine => 'Combine Invoice Items',
        };
    }
}
