<?php

declare(strict_types=1);

namespace App\Project\Properties;

use Mom\Data\AbstractInteger;

/**
 * Closed live top-level tickets. Read from the query's count; not a column.
 */
final class ClosedTicketsCount extends AbstractInteger
{
    public static bool $hasDBColumn = false;

    public static function getName(): string
    {
        return 'closed_tickets_count';
    }
}
