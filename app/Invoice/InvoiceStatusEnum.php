<?php

declare(strict_types=1);

namespace App\Invoice;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

enum InvoiceStatusEnum: string
{
    use Enum;
    use EnumOptions;

    case Draft = 'draft';
    case Paid = 'paid';
    case Sent = 'sent';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Paid => 'Paid',
            self::Sent => 'Sent',
            self::Void => 'Void',
        };
    }

    /**
     * Drafts are sent, and either can be voided. An invoice is never marked
     * paid by hand: it becomes paid when payments cover it.
     */
    public function canBecome(self $next): bool
    {
        return match ($next) {
            self::Sent => $this === self::Draft,
            self::Paid => false,
            self::Void => in_array($this, [self::Draft, self::Sent], true),
            self::Draft => false,
        };
    }
}
