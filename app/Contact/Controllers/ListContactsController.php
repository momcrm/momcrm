<?php

declare(strict_types=1);

namespace App\Contact\Controllers;

use App\Contact\Actions\FindContact;
use App\Contact\Contact;
use App\Contact\ContactEloquentModel;
use App\Contact\ContactRelationship;
use App\Customer\CustomerEloquentModel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ListContactsController
{
    public function __invoke(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString() ?: null;

        return Inertia::render('contacts/index', [
            'contacts' => FindContact::paginateForTeam((string) $request->user()?->current_team_id, $search)
                ->through(function (ContactEloquentModel $model): array {
                    $customer = $model->getRelation(ContactRelationship::Customer->value);

                    return [
                        ...Contact::fromEloquentModel($model)->toSummary(),
                        'customer_name' => $customer instanceof CustomerEloquentModel ? $customer->name : null,
                    ];
                }),
            'filters' => ['search' => $search],
        ]);
    }
}
