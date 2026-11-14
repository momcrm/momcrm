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
     * Drafts are sent, sent invoices are paid, and either can be voided.
     */
    public function canBecome(self $next): bool
    {
        return match ($next) {
            self::Sent => $this === self::Draft,
            self::Paid => $this === self::Sent,
            self::Void => in_array($this, [self::Draft, self::Sent], true),
            self::Draft => false,
        };
    }
}
