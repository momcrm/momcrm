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
        };
    }

    /**
     * The team's records of this kind that the person may see. Tickets and
     * projects obey project visibility, so nothing built on this reveals a
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
        };

        $query->withoutGlobalScopes()->where('team_id', $teamId);

        match ($this) {
            self::Ticket => ProjectVisibility::constrainTickets($query->getQuery(), $userId),
            self::Project => ProjectVisibility::constrainProjects($query->getQuery(), $userId),
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
            self::Customer, self::Project, self::Asset, self::Location, self::Service => ['name'],
            self::Contact => ['first_name', 'last_name'],
            self::Contract => ['title'],
            self::Invoice => [],
        };

        if ($this === self::Invoice) {
            // An invoice has no name, only its number: "INV-0042", "42".
            $number = preg_replace('/\D/', '', $search);

            if ($search !== '') {
                $number === '' ? $query->whereRaw('1 = 0') : $query->where('number', (int) $number);
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
            $record instanceof ServiceEloquentModel => $record->name,
            $record instanceof ContractEloquentModel => $record->title,
            $record instanceof InvoiceEloquentModel => Invoice::fromEloquentModel($record)->getReference(),
            default => $this->label(),
        };
    }

    /**
     * The page that shows a record. A contract has no page of its own: it
     * opens in a dialog on its customer's Contracts tab.
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
            self::Service => route('services.edit', $id),
            self::Contract => route('customers.contracts', ['customer' => $record->getAttribute('customer_id'), 'contract' => $id]),
            self::Invoice => route('invoices.show', $id),
        };
    }
}
