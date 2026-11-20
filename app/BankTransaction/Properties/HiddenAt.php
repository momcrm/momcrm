<?php

declare(strict_types=1);

namespace App\BankTransaction\Properties;

use Mom\Data\AbstractDate;

/**
 * When the line was put aside as not a customer's payment, such as a
 * transfer between the team's own accounts.
 */
final class HiddenAt extends AbstractDate
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'hidden_at';
    }
}
