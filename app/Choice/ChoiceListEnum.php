<?php

declare(strict_types=1);

namespace App\Choice;

use App\Shared\ColorEnum;
use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;
use App\Ticket\TicketStatusCategoryEnum;
use BackedEnum;

/**
 * The lists a team can customise. Each list says which categories its
 * choices may have and which choices a new team starts with.
 */
enum ChoiceListEnum: string
{
    use Enum;
    use EnumOptions;

    case TicketPriority = 'ticket_priority';
    case TicketStatus = 'ticket_status';

    public function label(): string
    {
        return match ($this) {
            self::TicketPriority => 'Ticket priorities',
            self::TicketStatus => 'Ticket statuses',
        };
    }

    /**
     * The enum of categories its choices are sorted into, or null when the list
     * has none. Every category needs at least one choice and a default.
     *
     * @return class-string<BackedEnum>|null
     */
    public function categoryEnum(): ?string
    {
        return match ($this) {
            self::TicketPriority => null,
            self::TicketStatus => TicketStatusCategoryEnum::class,
        };
    }

    /**
     * The categories its choices may have, as stored values.
     *
     * @return array<int, string>
     */
    public function categories(): array
    {
        $enum = $this->categoryEnum();

        return $enum === null
            ? []
            : array_map(fn (BackedEnum $case): string => (string) $case->value, $enum::cases());
    }

    /**
     * The choices a new team starts with, top to bottom.
     *
     * @return array<int, array{name: string, color: ColorEnum, icon: ?string, category: ?string, is_default: bool}>
     */
    public function defaults(): array
    {
        return match ($this) {
            self::TicketPriority => [
                ['name' => 'Urgent', 'color' => ColorEnum::Rose, 'icon' => 'chevrons-up', 'category' => null, 'is_default' => false],
                ['name' => 'High', 'color' => ColorEnum::Amber, 'icon' => 'chevron-up', 'category' => null, 'is_default' => false],
                ['name' => 'Normal', 'color' => ColorEnum::Slate, 'icon' => 'equal', 'category' => null, 'is_default' => true],
                ['name' => 'Low', 'color' => ColorEnum::Slate, 'icon' => 'chevron-down', 'category' => null, 'is_default' => false],
            ],
            self::TicketStatus => [
                ['name' => 'New', 'color' => ColorEnum::Slate, 'icon' => null, 'category' => TicketStatusCategoryEnum::Open->value, 'is_default' => true],
                ['name' => 'In progress', 'color' => ColorEnum::Indigo, 'icon' => null, 'category' => TicketStatusCategoryEnum::Open->value, 'is_default' => false],
                ['name' => 'Waiting on customer', 'color' => ColorEnum::Amber, 'icon' => null, 'category' => TicketStatusCategoryEnum::Open->value, 'is_default' => false],
                ['name' => 'Waiting on supplier', 'color' => ColorEnum::Blue, 'icon' => null, 'category' => TicketStatusCategoryEnum::Open->value, 'is_default' => false],
                ['name' => 'Closed', 'color' => ColorEnum::Green, 'icon' => null, 'category' => TicketStatusCategoryEnum::Closed->value, 'is_default' => true],
            ],
        };
    }
}
