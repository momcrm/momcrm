<?php

declare(strict_types=1);

namespace App\Choice;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Choice>
 */
final class ChoiceCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Choice::class;
    }
}
