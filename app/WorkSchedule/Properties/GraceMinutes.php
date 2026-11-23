<?php

declare(strict_types=1);

namespace App\WorkSchedule\Properties;

use Mom\Data\AbstractInteger;

/**
 * How many minutes after the start someone can clock in, or before the end
 * clock out, without being flagged late or leaving early.
 */
final class GraceMinutes extends AbstractInteger
{
    public const int MAX = 120;

    public static function getName(): string
    {
        return 'grace_minutes';
    }
}
