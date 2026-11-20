<?php

declare(strict_types=1);

namespace App\Portal\Actions;

use App\Contact\Contact;
use App\Contact\ContactCollection;
use App\Contact\ContactEloquentModel;
use App\Contact\Properties\PortalAccess;
use App\Shared\Table;
use App\Shared\Types;
use Illuminate\Database\Eloquent\Builder;

/**
 * The contacts someone can sign in to the portal as: every contact with their
 * email address and portal access, at any customer of any team.
 */
final class FindPortalContacts
{
    public static function forEmail(?string $email): ContactCollection
    {
        $query = self::query($email);

        return Contact::collect($query === null ? [] : $query->get()->all(), 'fromEloquentModel');
    }

    /**
     * Whether anyone with this address may use the portal.
     */
    public static function existForEmail(?string $email): bool
    {
        return self::query($email)?->exists() ?? false;
    }

    /**
     * The choices on the "Which customer?" page.
     *
     * @return list<array{id: string, name: string, customer: string, team: string}>
     */
    public static function choicesForEmail(?string $email): array
    {
        $query = self::query($email);

        if ($query === null) {
            return [];
        }

        return array_values($query
            ->addSelect([
                Table::Customers->value.'.name as customer_name',
                Table::Teams->value.'.name as team_name',
            ])
            ->get()
            ->map(fn (ContactEloquentModel $model): array => [
                'id' => $model->id,
                'name' => Contact::fromEloquentModel($model)->getFullName(),
                'customer' => Types::string($model->getAttribute('customer_name')),
                'team' => Types::string($model->getAttribute('team_name')),
            ])
            ->all());
    }

    /**
     * Contacts with the address and portal access, by provider then customer;
     * null for no address at all.
     *
     * @return Builder<ContactEloquentModel>|null
     */
    private static function query(?string $email): ?Builder
    {
        $email = mb_strtolower(mb_trim((string) $email));

        if ($email === '') {
            return null;
        }

        $contacts = Table::Contacts->value;

        return ContactEloquentModel::query()
            ->withoutGlobalScopes()
            ->select("{$contacts}.*")
            ->join(Table::Customers->value, Table::Customers->value.'.id', '=', "{$contacts}.customer_id")
            ->join(Table::Teams->value, Table::Teams->value.'.id', '=', "{$contacts}.team_id")
            ->whereRaw('LOWER(contacts.email) = ?', [$email])
            ->where("{$contacts}.".PortalAccess::getDatabaseTableColumnName(), true)
            ->orderBy(Table::Teams->value.'.name')
            ->orderBy(Table::Customers->value.'.name');
    }
}
