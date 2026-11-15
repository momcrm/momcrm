<?php

declare(strict_types=1);

namespace App\Ticket\Properties;

use Mom\Data\AbstractInteger;

/**
 * Subtasks. Read from the query's count; not a column.
 */
final class SubtasksCount extends AbstractInteger
{
    public static bool $hasDBColumn = false;

    public static function getName(): string
    {
        return 'subtasks_count';
    }
}
