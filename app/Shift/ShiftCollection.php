<?php

declare(strict_types=1);

namespace App\Shift;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Shift>
 */
final class ShiftCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Shift::class;
    }
}
