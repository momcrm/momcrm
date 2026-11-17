<?php

declare(strict_types=1);

namespace App\CustomObject\Properties;

use Mom\Data\AbstractInteger;

/**
 * The object's place in the team's list of objects, from 0 at the top.
 */
final class Position extends AbstractInteger
{
    public static function getName(): string
    {
        return 'position';
    }
}
