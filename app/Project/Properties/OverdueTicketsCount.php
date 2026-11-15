<?php

declare(strict_types=1);

namespace App\Project\Properties;

use Mom\Data\AbstractInteger;

/**
 * Open live top-level tickets past their due date. Read from the query's count; not a column.
 */
final class OverdueTicketsCount extends AbstractInteger
{
    public static bool $hasDBColumn = false;

    public static function getName(): string
    {
        return 'overdue_tickets_count';
    }
}
