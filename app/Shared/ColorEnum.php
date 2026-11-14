<?php

declare(strict_types=1);

namespace App\Shared;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

/**
 * The colours a project, group or label can be tagged with. The frontend maps
 * each value to a swatch in the theme.
 */
enum ColorEnum: string
{
    use Enum;
    use EnumOptions;

    case Amber = 'amber';
    case Blue = 'blue';
    case Green = 'green';
    case Indigo = 'indigo';
    case Rose = 'rose';
    case Slate = 'slate';
    case Teal = 'teal';
    case Violet = 'violet';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
