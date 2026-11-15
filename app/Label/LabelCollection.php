<?php

declare(strict_types=1);

namespace App\Label;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Label>
 */
final class LabelCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Label::class;
    }
}
