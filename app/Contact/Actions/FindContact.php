<?php

declare(strict_types=1);

namespace App\Contact\Actions;

use App\Contact\Contact;
use App\Contact\ContactCollection;
use App\Contact\ContactEloquentModel;
use App\Contact\ContactRelationship;
use App\Contact\Properties\CustomerId;
use App\Contact\Properties\Email;
use App\Contact\Properties\FirstName;
use App\Contact\Properties\Id;
use App\Contact\Properties\IsPrimary;
use App\Contact\Properties\LastName;
use App\Contact\Properties\TeamId;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class FindContact
{
    public static function byIdForTeam(?string $id, ?string $teamId): Contact
    {
        if ($id === null || $teamId === null) {
            return Contact::new();
        }

        return Contact::fromEloquentModel(self::query($teamId)->where(Id::getDatabaseTableColumnName(), $id)->first());
    }

    /**
     * @return LengthAwarePaginator<int, Contact>
     */
    public static function paginateForTeam(string $teamId, ?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        return self::query($teamId)
            ->with(ContactRelationship::Customer->value)
            ->when($search, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query
                ->where(FirstName::getDatabaseTableColumnName(), 'like', "%{$search}%")
                ->orWhere(LastName::getDatabaseTableColumnName(), 'like', "%{$search}%")
                ->orWhere(Email::getDatabaseTableColumnName(), 'like', "%{$search}%")))
            ->orderBy(FirstName::getDatabaseTableColumnName())
            ->orderBy(LastName::getDatabaseTableColumnName())
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (ContactEloquentModel $model): Contact => Contact::fromEloquentModel($model));
    }

    /**
     * A customer's contacts, the primary contact first.
     */
    public static function forCustomer(string $customerId, string $teamId): ContactCollection
    {
        $contacts = self::query($teamId)
            ->where(CustomerId::getDatabaseTableColumnName(), $customerId)
            ->orderByDesc(IsPrimary::getDatabaseTableColumnName())
            ->orderBy(FirstName::getDatabaseTableColumnName())
            ->get();

        return Contact::collect($contacts->all(), 'fromEloquentModel');
    }

    /**
     * @return Builder<ContactEloquentModel>
     */
    private static function query(string $teamId): Builder
    {
        return ContactEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId);
    }
}
