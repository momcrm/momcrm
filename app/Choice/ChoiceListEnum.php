<?php

declare(strict_types=1);

namespace App\Choice;

use App\Shared\HuePalette;
use App\Shared\Table;
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

    case TicketDepartment = 'ticket_department';
    case TicketLabel = 'ticket_label';
    case TicketPriority = 'ticket_priority';
    case TicketStatus = 'ticket_status';
    case TicketType = 'ticket_type';

    public function label(): string
    {
        return match ($this) {
            self::TicketDepartment => 'Ticket departments',
            self::TicketLabel => 'Ticket labels',
            self::TicketPriority => 'Ticket priorities',
            self::TicketStatus => 'Ticket statuses',
            self::TicketType => 'Ticket types',
        };
    }

    /**
     * What the list is for, in a sentence, for the Workflow page.
     */
    public function description(): string
    {
        return match ($this) {
            self::TicketDepartment => 'The team or desk a ticket belongs to.',
            self::TicketLabel => 'Tags for sorting and finding tickets. A ticket can have several.',
            self::TicketPriority => 'How urgent a ticket is, most urgent first.',
            self::TicketStatus => 'Where a ticket is in its life, grouped by whether it is still open.',
            self::TicketType => 'What kind of request a ticket is.',
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
            self::TicketDepartment, self::TicketLabel, self::TicketPriority, self::TicketType => null,
            self::TicketStatus => TicketStatusCategoryEnum::class,
        };
    }

    /**
     * The categories its choices may have, with labels, for pickers.
     *
     * @return list<array{value: string, label: string}>
     */
    public function categoryOptions(): array
    {
        return match ($this) {
            self::TicketDepartment, self::TicketLabel, self::TicketPriority, self::TicketType => [],
            self::TicketStatus => TicketStatusCategoryEnum::options(),
        };
    }

    /**
     * The icons its choices may show, or none when the list has no icons.
     *
     * @return list<string>
     */
    public function icons(): array
    {
        return match ($this) {
            self::TicketPriority => ['chevrons-up', 'chevron-up', 'equal', 'chevron-down', 'minus'],
            self::TicketDepartment, self::TicketLabel, self::TicketStatus, self::TicketType => [],
        };
    }

    /**
     * Where records point at the list's choices, so archiving one can move
     * them and deleting one can count or clear them. A pivot table links many
     * choices to one record and has no team column of its own.
     *
     * @return list<array{table: Table, column: string, isPivot: bool}>
     */
    public function usages(): array
    {
        return match ($this) {
            self::TicketDepartment => [['table' => Table::Tickets, 'column' => 'department_id', 'isPivot' => false]],
            self::TicketLabel => [['table' => Table::LabelTicket, 'column' => 'label_id', 'isPivot' => true]],
            self::TicketPriority => [['table' => Table::Tickets, 'column' => 'priority_id', 'isPivot' => false]],
            self::TicketStatus => [['table' => Table::Tickets, 'column' => 'status_id', 'isPivot' => false]],
            self::TicketType => [['table' => Table::Tickets, 'column' => 'type_id', 'isPivot' => false]],
        };
    }

    /**
     * Whether a record may have none of the list's choices, as a ticket may
     * have no labels, type or department. Such a list has no default, any of
     * its choices can be archived, and deleting one simply takes it off the
     * records that have it.
     */
    public function isOptional(): bool
    {
        return match ($this) {
            self::TicketDepartment, self::TicketLabel, self::TicketType => true,
            self::TicketPriority, self::TicketStatus => false,
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
     * @return array<int, array{name: string, hue: ?int, icon: ?string, category: ?string, is_default: bool}>
     */
    public function defaults(): array
    {
        return match ($this) {
            self::TicketDepartment => [
                ['name' => 'Support', 'hue' => HuePalette::BLUE, 'icon' => null, 'category' => null, 'is_default' => false],
                ['name' => 'Projects', 'hue' => HuePalette::VIOLET, 'icon' => null, 'category' => null, 'is_default' => false],
                ['name' => 'Accounts', 'hue' => HuePalette::TEAL, 'icon' => null, 'category' => null, 'is_default' => false],
            ],
            self::TicketLabel => [],
            self::TicketPriority => [
                ['name' => 'Urgent', 'hue' => HuePalette::ROSE, 'icon' => 'chevrons-up', 'category' => null, 'is_default' => false],
                ['name' => 'High', 'hue' => HuePalette::AMBER, 'icon' => 'chevron-up', 'category' => null, 'is_default' => false],
                ['name' => 'Normal', 'hue' => null, 'icon' => 'equal', 'category' => null, 'is_default' => true],
                ['name' => 'Low', 'hue' => null, 'icon' => 'chevron-down', 'category' => null, 'is_default' => false],
            ],
            self::TicketStatus => [
                ['name' => 'New', 'hue' => null, 'icon' => null, 'category' => TicketStatusCategoryEnum::Open->value, 'is_default' => true],
                ['name' => 'In progress', 'hue' => HuePalette::INDIGO, 'icon' => null, 'category' => TicketStatusCategoryEnum::Open->value, 'is_default' => false],
                ['name' => 'Waiting on customer', 'hue' => HuePalette::AMBER, 'icon' => null, 'category' => TicketStatusCategoryEnum::Open->value, 'is_default' => false],
                ['name' => 'Waiting on supplier', 'hue' => HuePalette::BLUE, 'icon' => null, 'category' => TicketStatusCategoryEnum::Open->value, 'is_default' => false],
                ['name' => 'Closed', 'hue' => HuePalette::GREEN, 'icon' => null, 'category' => TicketStatusCategoryEnum::Closed->value, 'is_default' => true],
            ],
            self::TicketType => [
                ['name' => 'Incident', 'hue' => HuePalette::RED, 'icon' => null, 'category' => null, 'is_default' => false],
                ['name' => 'Service request', 'hue' => HuePalette::BLUE, 'icon' => null, 'category' => null, 'is_default' => false],
                ['name' => 'Question', 'hue' => HuePalette::AMBER, 'icon' => null, 'category' => null, 'is_default' => false],
                ['name' => 'Task', 'hue' => null, 'icon' => null, 'category' => null, 'is_default' => false],
            ],
        };
    }
}
