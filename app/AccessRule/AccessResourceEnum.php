<?php

declare(strict_types=1);

namespace App\AccessRule;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

/**
 * The kinds of record access rules can cover.
 */
enum AccessResourceEnum: string
{
    use Enum;
    use EnumOptions;

    case Asset = 'asset';
    case Attachment = 'attachment';
    case BankTransaction = 'bank_transaction';
    case Charge = 'charge';
    case Comment = 'comment';
    case Contact = 'contact';
    case Contract = 'contract';
    case Customer = 'customer';
    case Group = 'group';
    case Invoice = 'invoice';
    case Location = 'location';
    case Milestone = 'milestone';
    case Payment = 'payment';
    case Project = 'project';
    case Record = 'record';
    case Secret = 'secret';
    case Service = 'service';
    case Ticket = 'ticket';

    public function label(): string
    {
        return match ($this) {
            self::Asset => 'Assets',
            self::Attachment => 'Files',
            self::BankTransaction => 'Bank feeds',
            self::Charge => 'One-off charges',
            self::Comment => 'Comments',
            self::Contact => 'Contacts',
            self::Contract => 'Contracts',
            self::Customer => 'Customers',
            self::Group => 'Groups',
            self::Invoice => 'Invoices',
            self::Location => 'Locations',
            self::Milestone => 'Milestones',
            self::Payment => 'Payments',
            self::Project => 'Projects',
            self::Record => 'Custom object records',
            self::Secret => 'Secrets',
            self::Service => 'Services',
            self::Ticket => 'Tickets',
        };
    }

    /**
     * The actions someone can take on this kind of record.
     *
     * @return array<int, AccessActionEnum>
     */
    public function actions(): array
    {
        return match ($this) {
            self::Service => [
                AccessActionEnum::Create,
                AccessActionEnum::Update,
                AccessActionEnum::Archive,
                AccessActionEnum::Restore,
                AccessActionEnum::Delete,
            ],
            self::Asset, self::Charge, self::Contact, self::Contract, self::Location, self::Milestone, self::Payment, self::Record => [
                AccessActionEnum::Create,
                AccessActionEnum::Update,
                AccessActionEnum::Delete,
            ],
            self::BankTransaction => [
                AccessActionEnum::Import,
                AccessActionEnum::Update,
                AccessActionEnum::Delete,
            ],
            self::Attachment => [
                AccessActionEnum::Create,
                AccessActionEnum::Update,
                AccessActionEnum::Delete,
            ],
            self::Comment => [
                AccessActionEnum::Create,
                AccessActionEnum::Delete,
            ],
            self::Customer => [
                AccessActionEnum::Create,
                AccessActionEnum::Import,
                AccessActionEnum::Update,
                AccessActionEnum::Delete,
            ],
            self::Group => [
                AccessActionEnum::Create,
                AccessActionEnum::Update,
                AccessActionEnum::ManageMembers,
                AccessActionEnum::Delete,
            ],
            self::Invoice => [
                AccessActionEnum::Create,
                AccessActionEnum::Update,
                AccessActionEnum::ChangeStatus,
            ],
            self::Project => [
                AccessActionEnum::Create,
                AccessActionEnum::Update,
                AccessActionEnum::ChangeVisibility,
                AccessActionEnum::ManageMembers,
                AccessActionEnum::Archive,
                AccessActionEnum::Restore,
                AccessActionEnum::Delete,
            ],
            self::Secret => [
                AccessActionEnum::Create,
                AccessActionEnum::Update,
                AccessActionEnum::Reveal,
                AccessActionEnum::Delete,
            ],
            self::Ticket => [
                AccessActionEnum::Create,
                AccessActionEnum::Update,
                AccessActionEnum::Move,
                AccessActionEnum::Close,
                AccessActionEnum::Reopen,
                AccessActionEnum::Archive,
                AccessActionEnum::Restore,
                AccessActionEnum::Delete,
            ],
        };
    }

    public function supports(AccessActionEnum $action): bool
    {
        return in_array($action, $this->actions(), true);
    }
}
