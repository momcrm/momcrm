<?php

declare(strict_types=1);

namespace App\Contact\Actions;

use App\Contact\Contact;
use App\Contact\ContactEloquentModel;
use App\Contact\Properties\CustomerId;
use App\Contact\Properties\Id;
use App\Contact\Properties\IsPrimary;
use App\Team\Scopes\CurrentTeamScope;

/**
 * When a contact becomes a customer's primary contact, the previous one stops being it.
 */
final class KeepOnePrimaryContact
{
    public static function forContact(Contact $contact): void
    {
        if (! $contact->getIsPrimary()->toBoolean()) {
            return;
        }

        ContactEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(CustomerId::getDatabaseTableColumnName(), $contact->getCustomerId()->toString())
            ->where(Id::getDatabaseTableColumnName(), '!=', $contact->getId()->toString())
            ->where(IsPrimary::getDatabaseTableColumnName(), true)
            ->get()
            ->each(fn (ContactEloquentModel $other): bool => $other->update([IsPrimary::getDatabaseTableColumnName() => false]));
    }
}
