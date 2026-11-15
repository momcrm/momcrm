<?php

declare(strict_types=1);

namespace App\Ticket;

use App\Contact\Contact;
use App\Contact\ContactEloquentModel;
use App\Contact\Properties\FirstName;
use App\Contact\Properties\TeamId as ContactTeamId;
use App\Customer\Actions\FindCustomer;
use App\Location\LocationEloquentModel;
use App\Location\Properties\Name as LocationName;
use App\Location\Properties\TeamId as LocationTeamId;
use App\Project\Actions\FindProject;
use App\Team\Scopes\CurrentTeamScope;
use App\TeamUser\Actions\FindTeamMembers;

/**
 * Everything the ticket form needs to fill its pickers. Contacts and locations
 * carry their customer, so the form can show only the chosen customer's.
 */
final class TicketFormOptions
{
    /**
     * @return array<string, mixed>
     */
    public static function forTeam(string $teamId, string $userId): array
    {
        return [
            'customers' => FindCustomer::optionsForTeam($teamId),
            'contacts' => ContactEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->where(ContactTeamId::getDatabaseTableColumnName(), $teamId)
                ->orderBy(FirstName::getDatabaseTableColumnName())
                ->get()
                ->map(fn (ContactEloquentModel $model): array => [
                    'id' => $model->id,
                    'name' => Contact::fromEloquentModel($model)->getFullName(),
                    'customer_id' => $model->customer_id,
                ]),
            'locations' => LocationEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->where(LocationTeamId::getDatabaseTableColumnName(), $teamId)
                ->orderBy(LocationName::getDatabaseTableColumnName())
                ->get()
                ->map(fn (LocationEloquentModel $model): array => ['id' => $model->id, 'name' => $model->name, 'customer_id' => $model->customer_id]),
            'members' => FindTeamMembers::optionsForTeam($teamId),
            'projects' => FindProject::optionsForTeam($teamId, $userId),
            'priorities' => TicketPriorityEnum::options(),
            'statuses' => TicketStatusEnum::options(),
        ];
    }
}
