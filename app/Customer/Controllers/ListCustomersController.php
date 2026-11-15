<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Customer\Actions\FindCustomer;
use App\Customer\Customer;
use App\Customer\CustomerEloquentModel;
use App\Shared\Types;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ListCustomersController
{
    public function __invoke(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString() ?: null;

        return Inertia::render('customers/index', [
            'customers' => FindCustomer::paginateForTeam((string) $request->user()?->current_team_id, $search)
                ->through(fn (CustomerEloquentModel $model): array => [
                    ...Customer::fromEloquentModel($model)->toSummary(),
                    'contacts_count' => Types::intOrZero($model->getAttribute('contacts_count')),
                ]),
            'filters' => ['search' => $search],
        ]);
    }
}
