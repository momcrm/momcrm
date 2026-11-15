<?php

declare(strict_types=1);

namespace App\Choice\Properties;

use Mom\Data\AbstractString;

/**
 * How the app treats records with this choice, such as `open` or `closed` for
 * a ticket status. Each list has its own categories (ChoiceListEnum), and
 * lists without behaviour, such as priorities, leave it null.
 */
final class Category extends AbstractString
{
    public static function getName(): string
    {
        return 'category';
    }
}
