<?php

declare(strict_types=1);

namespace App\Favorite;

use App\Shared\MorphMapKeyEnum;
use App\Shared\Table;
use App\Shared\Traits\Enum;

/**
 * The kinds of record someone can star. Values are morph map aliases.
 */
enum FavoritableTypeEnum: string
{
    use Enum;

    case Customer = 'customer';
    case Project = 'project';
    case Ticket = 'ticket';

    public function morphMapKey(): MorphMapKeyEnum
    {
        return MorphMapKeyEnum::from($this->value);
    }

    public function table(): Table
    {
        return match ($this) {
            self::Customer => Table::Customers,
            self::Project => Table::Projects,
            self::Ticket => Table::Tickets,
        };
    }
}
