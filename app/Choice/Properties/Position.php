<?php

declare(strict_types=1);

namespace App\Choice\Properties;

use Mom\Data\AbstractInteger;

/**
 * The choice's place in its list, from 0 at the top. For priorities it is
 * also the rank: the first is the most urgent.
 */
final class Position extends AbstractInteger
{
    public static function getName(): string
    {
        return 'position';
    }
}
