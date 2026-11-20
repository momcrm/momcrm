<?php

declare(strict_types=1);

namespace App\CustomObject;

use App\Asset\AssetEloquentModel;
use App\Contact\ContactEloquentModel;
use App\Contract\ContractEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Invoice\Invoice;
use App\Invoice\InvoiceEloquentModel;
use App\Location\LocationEloquentModel;
use App\Milestone\MilestoneEloquentModel;
use App\Project\ProjectEloquentModel;
use App\Project\ProjectVisibility;
use App\Service\ServiceEloquentModel;
use App\Shared\Traits\Enum;
use App\Ticket\TicketEloquentModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The kinds of record built into the app, described the way a team's own
 * custom objects are: what they are called, which records someone may see,
 * what a record's title is and where its page is.
 *
 * Custom fields, record links and the Linked records panel all read from
 * here, so a built-in kind is registered once.
 */
enum StandardObjectEnum: string
{
    use Enum;

    case Ticket = 'ticket';
    case Customer = 'customer';
    case Contact = 'contact';
    case Project = 'project';
    case Asset = 'asset';
    case Location = 'location';
    case Service = 'service';
    case Contract = 'contract';
    case Invoice = 'invoice';
    case Milestone = 'milestone';

    /**
     * What several of its records are called.
     */
    public function label(): string
    {
        return match ($this) {
            self::Ticket => 'Tickets',
            self::Customer => 'Customers',
            self::Contact => 'Contacts',
            self::Project => 'Projects',
            self::Asset => 'Assets',
            self::Location => 'Locations',
            self::Service => 'Services',
            self::Contract => 'Contracts',
            self::Invoice => 'Invoices',
            self::Milestone => 'Milestones',
        };
    }

    /**
     * What one of its records is called, after "a" or "an".
     */
    public function labelSingular(): string
    {
        return match ($this) {
            self::Ticket => 'ticket',
            self::Customer => 'customer',
            self::Contact => 'contact',
            self::Project => 'project',
            self::Asset => 'asset',
            self::Location => 'location',
            self::Service => 'service',
            self::Contract => 'contract',
            self::Invoice => 'invoice',
            self::Milestone => 'milestone',
        };
    }

    /**
     * The name of its icon in the frontend's object icon list.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Ticket => 'ticket',
            self::Customer => 'building',
            self::Contact => 'people',
            self::Project => 'briefcase',
            self::Asset => 'monitor',
            self::Location => 'pin',
            self::Service => 'wrench',
            self::Contract => 'file',
            self::Invoice => 'receipt',
            self::Milestone => 'flag',
        };
    }

    /**
     * The fields the app gives every record of this kind, as settings lists
     * them beside the team's own: what each is called and what it holds.
     * They cannot be renamed or removed.
     *
     * @return list<array{name: string, type: string}>
     */
    public function fields(): array
    {
        $fields = match ($this) {
            self::Ticket => [
                'Title' => 'Text', 'Description' => 'Long text', 'Status' => 'Choice', 'Priority' => 'Choice', 'Type' => 'Choice',
                'Assignee' => 'Team member', 'Customer' => 'Customer', 'Contact' => 'Contact', 'Location' => 'Location',
                'Project' => 'Project', 'Group' => 'Group', 'Labels' => 'Labels', 'Start date' => 'Date', 'Due date' => 'Date', 'Estimate' => 'Number',
            ],
            self::Customer => [
                'Name' => 'Text', 'Prefix' => 'Text', 'Type' => 'Choice', 'Status' => 'Choice', 'Billing' => 'Choice',
                'Email' => 'Text', 'Phone' => 'Text', 'Website' => 'Text', 'ABN' => 'Text', 'Notes' => 'Long text',
            ],
            self::Contact => [
                'First name' => 'Text', 'Last name' => 'Text', 'Customer' => 'Customer', 'Email' => 'Text', 'Position' => 'Text',
                'Work phone' => 'Text', 'Mobile phone' => 'Text', 'Main contact' => 'Checkbox',
            ],
            self::Project => [
                'Name' => 'Text', 'Description' => 'Long text', 'Status' => 'Choice', 'Visibility' => 'Choice', 'Customer' => 'Customer',
                'Lead' => 'Team member', 'Members' => 'Team members', 'Group' => 'Group', 'Labels' => 'Labels',
                'Start date' => 'Date', 'Due date' => 'Date', 'Colour' => 'Colour', 'Icon' => 'Icon',
            ],
            self::Asset => [
                'Name' => 'Text', 'Serial number' => 'Text', 'Status' => 'Choice', 'Location' => 'Location', 'Description' => 'Long text', 'Labels' => 'Labels',
            ],
            self::Location => [
                'Name' => 'Text', 'Street' => 'Text', 'Street line 2' => 'Text', 'Suburb' => 'Text', 'State' => 'Text',
                'Postcode' => 'Text', 'Country' => 'Text', 'Main location' => 'Checkbox',
            ],
            self::Service => [
                'Name' => 'Text', 'Status' => 'Choice', 'Quantity' => 'Number', 'Unit price' => 'Money', 'Billing period' => 'Number',
                'Start date' => 'Date', 'End date' => 'Date', 'Location' => 'Location', 'Note' => 'Long text',
            ],
            self::Contract => [
                'Title' => 'Text', 'Type' => 'Choice', 'Status' => 'Choice', 'Signed' => 'Date', 'Start date' => 'Date', 'End date' => 'Date',
                'Purchase order' => 'Text', 'Description' => 'Text', 'Notes' => 'Long text',
            ],
            self::Invoice => [
                'Number' => 'Number', 'Customer' => 'Customer', 'Status' => 'Choice', 'Issued' => 'Date', 'Due' => 'Date',
                'Tax rate' => 'Number', 'Purchase order' => 'Text', 'Line items' => 'Line items', 'Notes' => 'Long text',
            ],
            self::Milestone => [
                'Name' => 'Text', 'Project' => 'Project', 'Due date' => 'Date', 'Done' => 'Checkbox',
            ],
        };

        $list = [];

        foreach ($fields as $name => $type) {
            $list[] = ['name' => $name, 'type' => $type];
        }

        return $list;
    }

    /**
     * The team's records of this kind that the person may see. Tickets,
     * projects and milestones obey project visibility, so nothing built on this reveals a
     * members-only project.
     *
     * @return Builder<covariant Model>
     */
    public function visibleQuery(string $teamId, string $userId): Builder
    {
        $query = match ($this) {
            self::Ticket => TicketEloquentModel::query(),
            self::Customer => CustomerEloquentModel::query(),
            self::Contact => ContactEloquentModel::query(),
            self::Project => ProjectEloquentModel::query(),
            self::Asset => AssetEloquentModel::query(),
            self::Location => LocationEloquentModel::query(),
            self::Service => ServiceEloquentModel::query(),
            self::Contract => ContractEloquentModel::query(),
            self::Invoice => InvoiceEloquentModel::query(),
            self::Milestone => MilestoneEloquentModel::query(),
        };

        $query->withoutGlobalScopes()->where('team_id', $teamId);

        match ($this) {
            self::Ticket => ProjectVisibility::constrainTickets($query->getQuery(), $userId),
            self::Project => ProjectVisibility::constrainProjects($query->getQuery(), $userId),
            self::Milestone => ProjectVisibility::constrainMilestones($query->getQuery(), $userId),
            default => null,
        };

        return $query;
    }

    /**
     * Narrows a query of its records to those matching what someone typed
     * into a picker, and puts them in the order a picker lists them.
     *
     * @param  Builder<covariant Model>  $query
     */
    public function search(Builder $query, string $search): void
    {
        $columns = match ($this) {
            self::Ticket => ['title'],
            self::Customer, self::Project, self::Asset, self::Location, self::Service, self::Milestone => ['name'],
            self::Contact => ['first_name', 'last_name'],
            self::Contract => ['title'],
            self::Invoice => [],
        };

        if ($this === self::Invoice) {
            // An invoice has no name, only its number: "INV-0042", "42".
            $number = Invoice::numberIn($search);

            if ($search !== '') {
                $number === null ? $query->whereRaw('1 = 0') : $query->where('number', $number);
            }

            $query->orderByDesc('number');

            return;
        }

        if ($search !== '') {
            $query->where(function (Builder $query) use ($columns, $search): void {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'like', "%{$search}%");
                }

                if ($this === self::Ticket && ctype_digit(ltrim($search, '#'))) {
                    $query->orWhere('number', (int) ltrim($search, '#'));
                }
            });
        }

        if ($this === self::Ticket) {
            $query->orderByDesc('number');

            return;
        }

        foreach ($columns as $column) {
            $query->orderBy($column);
        }
    }

    /**
     * What a record is called in a link, a picker or a list.
     */
    public function title(Model $record): string
    {
        return match (true) {
            $record instanceof TicketEloquentModel => "#{$record->number} {$record->title}",
            $record instanceof CustomerEloquentModel => $record->name,
            $record instanceof ContactEloquentModel => trim("{$record->first_name} {$record->last_name}"),
            $record instanceof ProjectEloquentModel,
            $record instanceof AssetEloquentModel,
            $record instanceof LocationEloquentModel,
            $record instanceof ServiceEloquentModel,
            $record instanceof MilestoneEloquentModel => $record->name,
            $record instanceof ContractEloquentModel => $record->title,
            $record instanceof InvoiceEloquentModel => Invoice::fromEloquentModel($record)->getReference(),
            default => $this->label(),
        };
    }

    /**
     * The page that shows a record. A contract has no page of its own: it
     * opens in a dialog on its customer's Contracts tab. Nor has a milestone,
     * which is shown on its project's page.
     */
    public function url(Model $record): string
    {
        $id = $record->getKey();

        return match ($this) {
            self::Ticket => route('tickets.show', $id),
            self::Customer => route('customers.show', $id),
            self::Contact => route('contacts.edit', $id),
            self::Project => route('projects.show', $id),
            self::Asset => route('assets.edit', $id),
            self::Location => route('locations.edit', $id),
            self::Service => route('services.show', $id),
            self::Contract => route('customers.contracts', ['customer' => $record->getAttribute('customer_id'), 'contract' => $id]),
            self::Invoice => route('invoices.show', $id),
            self::Milestone => route('projects.show', $record->getAttribute('project_id')),
        };
    }
}
