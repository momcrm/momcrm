<?php

declare(strict_types=1);

namespace App\Ticket\Properties;

use Mom\Data\AbstractInteger;

/**
 * Closed subtasks. Read from the query's count; not a column.
 */
final class ClosedSubtasksCount extends AbstractInteger
{
    public static bool $hasDBColumn = false;

    public static function getName(): string
    {
        return 'closed_subtasks_count';
    }
}
