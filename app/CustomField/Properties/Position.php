<?php

declare(strict_types=1);

namespace App\CustomField\Properties;

use Mom\Data\AbstractInteger;

/**
 * The field's place among its record type's fields, from 0 at the top.
 */
final class Position extends AbstractInteger
{
    public static function getName(): string
    {
        return 'position';
    }
}
