<?php

declare(strict_types=1);

namespace App\Ticket;

use App\Choice\ChoiceListEnum;
use App\Shared\Table;
use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * What a team's ticket status means to the app, whatever the team calls it:
 * open tickets are still being worked on, closed ones are finished.
 */
enum TicketStatusCategoryEnum: string
{
    use Enum;
    use EnumOptions;

    case Closed = 'closed';
    case Open = 'open';

    public function label(): string
    {
        return match ($this) {
            self::Closed => 'Closed',
            self::Open => 'Open',
        };
    }

    /**
     * The ids of every ticket status in this category, as a subquery for
     * `whereIn('status_id', ...)`, so "open tickets" means the same everywhere.
     */
    public function statusIds(): Builder
    {
        return DB::table(Table::Choices->value)
            ->select('id')
            ->where('list', ChoiceListEnum::TicketStatus->value)
            ->where('category', $this->value);
    }
}
